<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\Database\UpdateCommand;

abstract class AbstractMapper implements MapperInterface
{
    protected $orm;

    protected $class;

    protected $table;

    protected $primaryKey;

    public function __construct(ORMInterface $orm, $class)
    {
        $this->orm = $orm;
        $this->class = $class;

        $this->table = $this->orm->getSchema()->define($class, Schema::TABLE);
        $this->primaryKey = $this->orm->getSchema()->define($class, Schema::PRIMARY_KEY);
    }

    public function init()
    {
        $class = $this->class;

        return new $class;
    }

    public function queueStore($entity): CommandPromiseInterface
    {
        $state = $this->orm->getHeap()->get($entity);

        if ($state == null) {
            // todo: make sure that no save can happen after the heap reset
            $cmd = $this->buildInsert($entity);
        } else {
            $cmd = $this->buildUpdate($entity, $state);
        }

        return $this->orm->getRelationMap(get_class($entity))->queueRelations($entity, $cmd);
    }

    public function queueDelete($entity): CommandInterface
    {
        $state = $this->orm->getHeap()->get($entity);

        // todo: check state
        return $this->buildDelete($entity, $state);
    }

    abstract protected function getFields($entity): array;

    // todo: in the heap?
    //  abstract protected function setField($entity, $field, $value);

    protected function buildInsert($entity): CommandPromiseInterface
    {
        $data = $this->getFields($entity);
        $state = new State(
            $data[$this->primaryKey] ?? null,
            State::SCHEDULED_INSERT,
            $data
        );

        unset($data[$this->primaryKey]);

        $insert = new InsertCommand($this->orm->getDatabase($entity), $this->table, $data);

        // we are managed at this moment
        $this->orm->getHeap()->attach($entity, $state);

        $insert->onExecute(function (InsertCommand $command) use ($entity, $state) {
            $state->setPrimaryKey($this->primaryKey, $command->getPrimaryKey());
        });

        $insert->onComplete(function (InsertCommand $command) use ($entity, $state) {
            $state->setState(State::LOADED);

            $this->hydrate($entity, [
                $this->primaryKey => $command->getPrimaryKey()
            ]);

            // todo: update entity path
            $state->setPrimaryKey($this->primaryKey, $command->getPrimaryKey());

            $this->hydrate($entity, $command->getContext());
            $state->setData($command->getContext());
        });

        $insert->onRollBack(function (InsertCommand $command) use ($entity, $state) {
            $this->orm->getHeap()->detach($entity);
        });

        return $insert;
    }

    protected function buildUpdate($entity, State $state): CommandPromiseInterface
    {
        $oData = $state->getData();
        $eData = $this->getFields($entity);

        // todo: calc diff
        $uData = $this->getFields($entity) + $state->getData();
        $pK = $uData[$this->primaryKey] ?? null;
        unset($uData[$this->primaryKey]);

        // todo: pack changes (???) depends on mode (USE ALL FOR NOW)

        $update = new UpdateCommand(
            $this->orm->getDatabase($entity),
            $this->table,
            array_diff($eData, $oData), // todo: make it optional
            [$this->primaryKey => $pK],
            $pK
        );

        $current = $state->getState();
        $state->setState(State::SCHEDULED_UPDATE);
        $state->setData($uData);

        $state->onUpdate(function (State $state) use ($update) {
            $update->setWhere([$this->primaryKey => $state->getPrimaryKey()]);
            $update->setPrimaryKey($state->getPrimaryKey());
        });

        $update->onComplete(function (UpdateCommand $command) use ($entity, $state) {
            $state->setState(State::LOADED);

            $this->hydrate($entity, $command->getContext());
            $state->setData($command->getContext());
        });

        $update->onRollBack(function () use ($state, $current) {
            $state->setState($current);
            //todo: rollback
        });

        return $update;
    }

    protected function buildDelete($entity, State $state): CommandInterface
    {
        // todo: better primary key fetch

        $delete = new DeleteCommand(
            $this->orm->getDatabase($entity),
            $this->table,
            [$this->primaryKey => $state->getPrimaryKey() ?? $this->getFields($entity)[$this->primaryKey] ?? null]
        );

        $current = $state->getState();

        $state->setState(State::SCHEDULED_DELETE);

        $state->onUpdate(function (State $state) use ($delete) {
            $delete->setWhere([$this->primaryKey => $state->getPrimaryKey()]);
        });

        $delete->onComplete(function (DeleteCommand $command) use ($entity) {
            $this->orm->getHeap()->detach($entity);
        });

        $delete->onRollBack(function () use ($state, $current) {
            $state->setState($current);
        });

        return $delete;
    }
}