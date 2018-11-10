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
use Spiral\ORM\Command\NullCommand;

abstract class AbstractMapper implements MapperInterface
{
    protected $orm;

    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
    }

    public function queueStore($entity): CommandInterface
    {
        if (!$this->orm->getHeap()->hasInstance($entity)) {
            return $this->buildInsert($entity);
        }

        return new NullCommand();
    }

    public function queueDelete($entity): CommandInterface
    {
        echo 'delete';

        return new NullCommand();
    }

    abstract protected function getFields($entity): array;

    // todo: in the heap?
    abstract protected function setField($entity, $field, $value);

    // todo: from the heap?
    abstract protected function getField($entity, $field);

    protected function buildInsert($entity): CommandPromiseInterface
    {
        $schema = $this->orm->getSchema();
        $class = get_class($entity);
        $primaryKey = $schema->define($class, Schema::PRIMARY_KEY);

        $data = $this->getFields($entity);
        unset($data[$primaryKey]);

        $insert = new InsertCommand(
            $this->orm->getDatabase($class),
            $schema->define($class, Schema::TABLE),
            $data
        );

        // todo: LAST INSERT

        $insert->onComplete(function (InsertCommand $command) use ($primaryKey, $entity) {
            $this->setField($entity, $primaryKey, $command->getPrimaryKey());

            // hydrate all context values (todo: make multiple)
            foreach ($command->getContext() as $name => $value) {
                $this->setField($entity, $name, $value);
            }
        });

        // todo: events?

        return $insert;
    }


}