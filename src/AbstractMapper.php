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
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\Database\UpdateCommand;
use Spiral\ORM\Command\NullCommand;

abstract class AbstractMapper implements MapperInterface
{
    protected $orm;

    protected $class;

    public function __construct(ORMInterface $orm, $class)
    {
        $this->orm = $orm;
        $this->class = $class;
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
            return $this->buildInsert($entity);
        }

        return $this->buildUpdate($entity, $state);
    }

    public function queueDelete($entity): CommandInterface
    {
        echo 'delete';

        return new NullCommand();
    }

    abstract protected function getFields($entity): array;

    // todo: in the heap?
    abstract protected function setField($entity, $field, $value);

    protected function buildInsert($entity): CommandPromiseInterface
    {
        $schema = $this->orm->getSchema();
        $class = get_class($entity);
        $primaryKey = $schema->define($class, Schema::PRIMARY_KEY);

        $data = $this->getFields($entity);
        $state = new State(
            $data[$primaryKey] ?? null,
            State::SCHEDULED_INSERT,
            $data
        );

        unset($data[$primaryKey]);

        $insert = new InsertCommand(
            $this->orm->getDatabase($class),
            $schema->define($class, Schema::TABLE),
            $data
        );

        $state->setCommand($insert);

        // we are managed at this moment
        $this->orm->getHeap()->attach($entity, $state);

        $insert->onComplete(function (InsertCommand $command) use ($primaryKey, $entity, $state) {
            $state->setCommand(null);
            $state->setState(State::LOADED);

            $this->setField($entity, $primaryKey, $command->getPrimaryKey());

            // todo: update entity path
            $state->setPrimaryKey($primaryKey, $command->getPrimaryKey());

            // hydrate all context values
            foreach ($command->getContext() as $name => $value) {
                $this->setField($entity, $name, $value);
                $state->setField($name, $value);
            }
        });

        $insert->onRollBack(function (InsertCommand $command) use ($entity, $state) {
            $state->setCommand(null);
            $this->orm->getHeap()->detach($entity);
        });

        return $insert;
    }

    protected function buildUpdate($entity, State $state): CommandPromiseInterface
    {
        $schema = $this->orm->getSchema();
        $class = get_class($entity);
        $primaryKey = $schema->define($class, Schema::PRIMARY_KEY);

        $data = $this->getFields($entity);

        // todo: calc diff
        $uData = $data + $state->getData();
        unset($uData[$primaryKey]);

        // todo: pack changes (???) depends on mode (USE ALL FOR NOW)

        $update = new UpdateCommand(
            $this->orm->getDatabase($class),
            $schema->define($class, Schema::TABLE),
            $uData,
            [$primaryKey => $data[$primaryKey] ?? null],
            $data[$primaryKey] ?? null
        );

        $current = $state->getState();
        $state->setState(State::SCHEDULED_UPDATE);

        // todo: get from the state?
        if (!empty($state->getCommandPromise())) {
            $state->getCommandPromise()->onExecute(function (
                CommandPromiseInterface $command
            ) use ($primaryKey, $update) {
                $update->setWhere([$primaryKey => $command->getPrimaryKey()]);
                $update->setPrimaryKey($command->getPrimaryKey());
            });
        }

        $update->onComplete(function (UpdateCommand $command) use ($entity, $state) {
            $state->setState(State::LOADED);

            // hydrate all context values
            foreach ($command->getContext() as $name => $value) {
                $this->setField($entity, $name, $value);
                $state->setField($name, $value);
            }
        });

        $update->onRollBack(function () use ($state, $current) {
            $state->setState($current);
        });

        return $update;
    }
}