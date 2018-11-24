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
use Spiral\ORM\Command\Control\PrimarySequence;
use Spiral\ORM\Promise\Promise;
use Spiral\ORM\Selector;
use Spiral\ORM\State;
use Spiral\ORM\StateInterface;

class HasOneRelation extends AbstractRelation
{
    public function initPromise(State $state, $data)
    {
        // todo: here we need paths (!)

        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return null;
        }

        if ($this->orm->getHeap()->hasPath("{$this->class}:{$this->outerKey}.$innerKey")) {
            return $this->orm->getHeap()->getPath("{$this->class}:{$this->outerKey}.$innerKey");
        }

        // todo: can i unify it?
        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return null;
        }

        return new Promise(
            [$this->outerKey => $innerKey]
            , function () use ($innerKey) {
            // todo: check in map

                if ($this->orm->getHeap()->hasPath("{$this->class}:{$this->outerKey}.$innerKey")) {
                return $this->orm->getHeap()->getPath("{$this->class}:{$this->outerKey}.$innerKey");
            }

            $selector = new Selector($this->orm, $this->class);
            $selector->where([$this->outerKey => $innerKey]);

            return $selector->fetchOne();
        });
    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        ContextualInterface $parent,
        $entity,
        StateInterface $state,
        $related,
        $original
    ): CommandInterface {
        $sequence = new PrimarySequence();

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