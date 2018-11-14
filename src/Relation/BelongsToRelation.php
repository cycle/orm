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
use Spiral\ORM\Exception\Relation\NullException;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

// do not throw save?
class BelongsToRelation extends AbstractRelation
{
    const LEADING = true;

    // todo: move to the strategy
    public function queueChange(
        $parent,
        State $state,
        CommandPromiseInterface $command
    ): CommandInterface {
        $related = $this->getRelated($parent);
        $orig = $state->getRelation($this->relation);

        if ($related === null && !$this->define(Relation::NULLABLE)) {
            throw new NullException(
                "Relation `{$this->class}`.`{$this->relation}` can not be null"
            );
        }

        //  $orig = $state->getRelation($this->relation);

        $state->setRelation($this->relation, $related);

        //   $relState = $this->orm->getHeap()->get($related);
        //            if (!empty($relState)) {
        //                $relState->addReference();
        //                if ($relState->getRefCount() > 2) {
        //                    // todo: detect if it's the same parent over and over again?
        //                    return new NullCommand();
        //                }
        //            }

        // todo: ref-count as part of relation (do not walk thought ref-link more than once)
        // todo: but what if child has been added...
        // todo: big subject to think about, make tests first

        if (!is_null($related)) {
            $inner = $this->orm->getMapper($related)->queueStore($related);

            $inner->onExecute(function (CommandPromiseInterface $inner) use ($command, $related) {
                $command->setContext(
                    $this->schema[Relation::INNER_KEY],
                    $this->lookupKey($this->schema[Relation::OUTER_KEY], $related, $inner)
                );
            });
        } else {
            $command->setContext($this->schema[Relation::INNER_KEY], null);

            return new NullCommand();
        }

        return $inner;
    }
}