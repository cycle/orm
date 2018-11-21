<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Database\DeleteCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\Database\UpdateCommand;
use Spiral\ORM\Command\NullCommand;

// todo: events
abstract class AbstractMapper implements MapperInterface
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    protected $orm;

    protected $class;

    protected $table;

    protected $primaryKey;

    protected $children;

    protected $columns;

    public function __construct(ORMInterface $orm, $class)
    {
        $this->orm = $orm;
        $this->class = $class;

        // todo: mass export
        $this->columns = $this->orm->getSchema()->define($class, Schema::COLUMNS);
        $this->table = $this->orm->getSchema()->define($class, Schema::TABLE);
        $this->primaryKey = $this->orm->getSchema()->define($class, Schema::PRIMARY_KEY);
        $this->children = $this->orm->getSchema()->define($class, Schema::CHILDREN) ?? [];
    }

    public function entityClass(array $data): string
    {
        $class = $this->class;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }

    public function prepare(array $data): array
    {
        $class = $this->entityClass($data);

        return [new $class, $data];
    }

    public function queueStore($entity): ContextualInterface
    {
        $state = $this->orm->getHeap()->get($entity);

        if ($state == null || $state->getState() == State::NEW) {
            $cmd = $this->queueCreate($entity, $state);
        } else {
            $cmd = $this->queueUpdate($entity, $state);
        }

        return $cmd;
    }

    public function queueDelete($entity): CommandInterface
    {
        $state = $this->orm->getHeap()->get($entity);
        if ($state == null) {
            // todo: this should not happen, todo: need nullable delete
            return new NullCommand();
        }

        // todo: delete relations as well

        return $this->buildDelete($entity, $state);
    }

    protected function getColumns($entity): array
    {
        return array_intersect_key($this->extract($entity), array_flip($this->columns));
    }

    protected function queueCreate($entity, StateInterface &$state = null): ContextualInterface
    {
        $columns = $this->getColumns($entity);

        $class = get_class($entity);
        if ($class != $this->class) {
            // possibly children
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                }
            }

            // todo: exception
        }

        if (is_null($state)) {
            // todo: do we need to track PK?
            $state = new State(State::NEW, $columns);
            $this->orm->getHeap()->attach($entity, $state);
        }

        $state->setState(State::SCHEDULED_INSERT);

        unset($columns[$this->primaryKey]);

        $insert = new InsertCommand($this->orm->getDatabase($entity), $this->table, $columns);

        // we are managed at this moment

        $insert->onExecute(function (InsertCommand $command) use ($entity, $state) {
            $state->setData([$this->primaryKey => $command->getInsertID()]);
        });

        $insert->onComplete(function (InsertCommand $command) use ($entity, $state) {
            $state->setState(State::LOADED);

            // todo: update entity path

            $this->hydrate(
                $entity,
                [$this->primaryKey => $command->getInsertID()] + $command->getContext()
            );

            // todo: replace with data (!!)
            $state->setData($command->getContext());

            // todo: mount path to the entity!!!
        });

        $insert->onRollBack(function (InsertCommand $command) use ($entity, $state) {
            $this->orm->getHeap()->detach($entity);
        });

        return $insert;
    }

    protected function queueUpdate($entity, StateInterface $state): ContextualInterface
    {
        $eData = $this->getColumns($entity);
        $oData = $state->getData();
        $cData = array_diff($eData, $oData);

        // todo: pack changes (???) depends on mode (USE ALL FOR NOW)

        $update = new UpdateCommand(
            $this->orm->getDatabase($entity),
            $this->table,
            $cData,
            [$this->primaryKey => $state->getData()[$this->primaryKey] ?? $eData[$this->primaryKey] ?? null]
        );

        $current = $state->getState();
        $state->setState(State::SCHEDULED_UPDATE);
        $state->setData($cData);

        $state->onChange(function (State $state) use ($update) {
            $update->setWhere($this->primaryKey, $state->getData()[$this->primaryKey]);
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

    protected function buildDelete($entity, StateInterface $state): CommandInterface
    {
        // todo: better primary key fetch

        $delete = new DeleteCommand(
            $this->orm->getDatabase($entity),
            $this->table,
            // todo: uuid?
            [$this->primaryKey => $state->getData()[$this->primaryKey] ?? $this->extract($entity)[$this->primaryKey] ?? null]
        );

        $current = $state->getState();

        $state->setState(State::SCHEDULED_DELETE);

        $state->onChange(function (State $state) use ($delete) {
            $delete->setWhere($this->primaryKey, $state->getData()[$this->primaryKey]);
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