<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\ContextualSequence;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasOneRelation extends AbstractRelation
{
    /**
     * @inheritdoc
     */
    public function queueRelation($entity, State $state, $related, $original): CommandInterface
    {
        $sequence = new ContextualSequence();

        if (!empty($original) && $related !== $original) {
            $this->deleteOriginal($sequence, $original);
        }

        if (empty($related)) {
            // nothing to persist
            return $sequence;
        }

        // polish even more?
        $relStore = $this->orm->getMapper($related)->queueStore($related);
        $sequence->addPrimary($relStore);

        $relState = $this->getState($related);
        $relState->addReference();

        $this->promiseContext(
            $relStore,
            $state,
            $this->define(Relation::INNER_KEY),
            $relState,
            $this->define(Relation::OUTER_KEY)
        );

        // todo: morph key

        return $sequence;
    }

    /**
     * Delete original related entity of no other objects reference to it.
     *
     * @param ContextualSequence $sequence
     * @param object             $original
     */
    protected function deleteOriginal(ContextualSequence $sequence, $original)
    {
        $oriState = $this->getState($original);
        $oriState->decReference();

        // only delete original child when no other objects claim it
        $sequence->addCommand(
            new Condition(
                $this->orm->getMapper($original)->queueDelete($original),
                function () use ($oriState) {
                    return !$oriState->hasReferences();
                }
            )
        );
    }
}