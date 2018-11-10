<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Command\NullCommand;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

class HasOneRelation extends AbstractRelation
{
    public function queueStore($parent, CommandPromiseInterface $command): CommandInterface
    {
        $related = $this->getRelated($parent);
        if (empty($related)) {
            return new NullCommand();
        }

        $inner = $this->orm->getMapper(get_class($related))->queueStore($related);

        // syncing (TODO: CHECK IF NOT SYNCED ALREADY)
        $command->onExecute(function (CommandPromiseInterface $command) use ($inner, $parent) {
            $inner->addContext(
                $this->schema[Relation::OUTER_KEY],
                $this->lookupKey($this->schema[Relation::INNER_KEY], $parent, $command)
            );

            // todo: MORPH KEY
        });

        return $inner;
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