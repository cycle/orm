<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualCommandInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\ContextSequence;
use Spiral\ORM\Relation;
use Spiral\ORM\State;

class HasOneRelation extends AbstractRelation
{
    use Relation\Traits\PromiseTrait;

    /**
     * @inheritdoc
     */
    public function queueChange(
        $parent,
        State $state,
        $related,
        $original,
        ContextualCommandInterface $command
    ): CommandInterface {
        $state->setRelation($this->relation, $related);

        $sequence = new ContextSequence();

        if (!empty($original) && $related !== $original) {
            $this->deleteOriginal($sequence, $original);
        }

        if (empty($related)) {
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

        return $sequence;
    }

    /**
     * Delete original related entity of no other objects reference to it.
     *
     * @param ContextSequence $sequence
     * @param object          $original
     */
    protected function deleteOriginal(ContextSequence $sequence, $original)
    {
        $oldState = $this->getState($original);
        $oldState->decReference();

        // only delete original child when no other objects claim it
        $sequence->addCommand(new Condition(
            $this->orm->getMapper($original)->queueDelete($original),
            function () use ($oldState) {
                return !$oldState->hasReferences();
            }
        ));
    }
}