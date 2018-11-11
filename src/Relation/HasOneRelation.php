<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasOneRelation extends AbstractRelation
{
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface {
        $related = $this->getRelated($parent);
        $orig = $state->getRelation($this->relation);

        $chain = new ChainCommand();

        // delete, we need to think about replace
        if (!empty($orig) && empty($related)) {
            // delete?
            return $this->orm->getMapper(get_class($orig))->queueDelete($orig);
        }

        if (!empty($orig) && !empty($related) && $orig !== $related) {
            $chain->addCommand($this->orm->getMapper(get_class($orig))->queueDelete($orig));
        }

        $inner = $this->orm->getMapper(get_class($related))->queueStore($related);
        $chain->addTargetCommand($inner);

        // syncing (TODO: CHECK IF NOT SYNCED ALREADY)
        $command->onExecute(function (CommandPromiseInterface $command) use ($inner, $parent) {
            $inner->addContext(
                $this->schema[Relation::OUTER_KEY],
                $this->lookupKey($this->schema[Relation::INNER_KEY], $parent, $command)
            );

            // todo: MORPH KEY
        });

        return $chain;
    }
}