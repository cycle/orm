<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Relation;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Condition;
use Spiral\ORM\Command\Control\Nil;
use Spiral\ORM\Command\Control\PrimarySequence;
use Spiral\ORM\Point;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Util\Promise;

// todo: NOT DELETE VIA CONTEXT KEY BEING UPDATED (!)
class HasOneRelation extends AbstractRelation
{
    public function initPromise(Point $state, $data): array
    {
        // todo: here we need paths (!)

        if (empty($innerKey = $this->fetchKey($state, $this->innerKey))) {
            return [null, null];
        }

        if ($this->orm->getHeap()->hasPath("{$this->class}:{$this->outerKey}.$innerKey")) {
            $i = $this->orm->getHeap()->getPath("{$this->class}:{$this->outerKey}.$innerKey");
            return [$i, $i];
        }

        $pr = new Promise(
            [$this->outerKey => $innerKey],
            function ($context) use ($innerKey) {
                // todo: check in map

                // todo: improve it
                if ($this->orm->getHeap()->hasPath("{$this->class}:{$this->outerKey}.$innerKey")) {
                    return $this->orm->getHeap()->getPath("{$this->class}:{$this->outerKey}.$innerKey");
                }

                return $this->orm->getMapper($this->class)->getRepository()->findOne($context);
            }
        );

        return [$pr, $pr];

    }

    /**
     * @inheritdoc
     */
    public function queueRelation(
        CarrierInterface $parentCommand,
        $parentEntity,
        Point $parentState,
        $related,
        $original
    ): CommandInterface {
        $sequence = new PrimarySequence();

        if (!empty($original) && $related !== $original) {
            if ($original instanceof PromiseInterface) {
                $original = $original->__resolve();
            }

            if (!empty($original)) {
                $sequence->addCommand($this->deleteOriginal($original));
            }
        }

        // todo: unify?
        if ($related instanceof PromiseInterface) {
            $related = $related->__resolve();
        }

        // todo: make it better
        if (empty($related)) {
            if (count($sequence) === 0) {
                return new Nil();
            }

            // nothing to persist
            return $sequence;
        }

        // store command with dependency on parent key
        $sequence->addPrimary($this->addDependency(
            $parentState,
            $this->innerKey,
            $this->orm->queueStore($related),
            $this->getPoint($related, +1),
            $this->outerKey
        ));

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
        $point = $this->getPoint($original, -1);

        // only delete original child when no other objects claim it
        return new Condition($this->orm->queueDelete($original), function () use ($point) {
            return !$point->hasClaims();
        });
    }
}