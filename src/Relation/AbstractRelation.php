<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\RelationInterface;
use Spiral\ORM\Schema;
use Spiral\ORM\State;

abstract class AbstractRelation implements RelationInterface
{
    /**
     * @invisible
     * @var ORMInterface
     */
    protected $orm;

    protected $class;

    protected $relation;

    protected $schema;

    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->relation = $relation;
        $this->schema = $schema;
    }

    public function init($data)
    {
        if (is_null($data)) {
            return null;
        }

        // todo: array?
        // todo: pretty easy?

        return $this->orm->make($this->class, $data, State::LOADED);
    }

    protected function getRelated($entity)
    {
        return $this->orm->getMapper($this->class)->getField($entity, $this->relation);
    }

    // todo: optimize column access, state access
    protected function lookupKey($key, $entity, CommandPromiseInterface $command = null)
    {
        if (!empty($command)) {
            $context = $command->getContext();
            if (!empty($context[$key])) {
                //Key value found in a context
                return $context[$key];
            }

            if ($key == $this->orm->getSchema()->define($this->class, Schema::PRIMARY_KEY)) {
                return $command->getPrimaryKey();
            }
        }

        return $this->orm->getMapper($this->class)->getField($entity, $key);
    }
}