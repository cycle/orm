<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\ContextualSequence;
use Spiral\ORM\StateInterface;

class HasOneRelation extends AbstractRelation
{
    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $command,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        $sequence = new ContextualSequence();

        if (!empty($original) && $related !== $original) {
            $sequence->addCommand($this->deleteOriginal($original));
        }

        if (empty($related)) {
            // nothing to persist
            return $sequence;
        }

        $relStore = $this->orm->queueStore($related);
        $relState = $this->getState($related);
        $relState->addReference();

        $this->promiseContext($relStore, $state, $this->innerKey, $relState, $this->outerKey);

        // todo: morph key

        $sequence->addPrimary($relStore);

        return $sequence;
    }

    /**
     * Delete original related entity of no other objects reference to it.
     *
     * @param object $original
     * @return CommandInterface
     */
    protected function deleteOriginal($original): CommandInterface
    {
        $oriState = $this->getState($original);
        $oriState->decReference();

        // only delete original child when no other objects claim it
        return new Condition(
            $this->orm->queueDelete($original),
            function () use ($oriState) {
                return !$oriState->hasReferences();
            }
        );
    }
}