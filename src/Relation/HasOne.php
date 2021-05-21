<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides the ability to own and forward context values to child entity.
 */
class HasOne extends AbstractRelation
{
    use PromiseOneTrait;

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        if ($tuple->task === Tuple::TASK_STORE) {
            $this->queueStore($pool, $tuple, $related);
        } else {
            $this->queueDelete($pool, $tuple, $related);
        }
    }

    private function queueStore(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
            $node->setRelation($this->getName(), $original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($related === null) {
            if ($original === null) {
                $this->setStatus(RelationInterface::STATUS_RESOLVED);
                return;
            }
            // todo: on transaction rollback?
            $node->getState()->setRelation($this->getName(), $related);
            $this->setStatus(RelationInterface::STATUS_RESOLVED);
            $this->deleteChild($pool, $original);
            return;
        }
        $node->getState()->setRelation($this->getName(), $related);

        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        $changes = $node->getData();

        if (!in_array($tuple->status, [
            Tuple::STATUS_WAITED,
            Tuple::STATUS_PREPARING,
        ], true)) {
            foreach ($this->innerKeys as $i => $innerKey) {
                if (isset($changes[$innerKey])) {
                    $rNode->register($this->outerKeys[$i], $changes[$innerKey]);
                }
            }
        }
        $this->setStatus(RelationInterface::STATUS_RESOLVED);
        if ($original !== null && $original !== $related) {
            $this->deleteChild($pool, $original);
        }
        $pool->attachStore($related, true, $rNode);
    }
    private function queueDelete(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }
        $resolved = false;
        if ($original !== null) {
            $originNode = $this->getNode($original);
            if ($originNode !== null && $originNode->getStatus() === Node::MANAGED) {
                $this->setStatus(RelationInterface::STATUS_DEFERRED);
                $this->deleteChild($pool, $original);
            } else {
                $resolved = true;
            }
        } else {
            $resolved = true;
        }
        $resolved and $this->setStatus(RelationInterface::STATUS_RESOLVED);
        if ($related === $original) {
            return;
        }
        $relatedNode = $this->getNode($related);
        if ($relatedNode === null || $relatedNode->getStatus() !== Node::MANAGED) {
            return;
        }
        $this->setStatus(RelationInterface::STATUS_DEFERRED);
        $this->deleteChild($pool, $related, $relatedNode);
    }

    /**
     * Delete original related entity of no other objects reference to it.
     */
    private function deleteChild(Pool $pool, object $child, ?Node $relatedNode = null): ?Tuple
    {
        $relatedNode = $relatedNode ?? $this->getNode($child);
        if ($relatedNode->getStatus() !== Node::MANAGED) {
            return null;
        }

        if ($this->isNullable()) {
            foreach ($this->outerKeys as $outerKey) {
                $relatedNode->getState()->register($outerKey, null, true);
            }
            return $pool->attachStore($child, false, $relatedNode, $relatedNode->getState());
        }

        return $pool->attachDelete($child, $this->isCascade(), $relatedNode);
    }

    public function queue(object $entity, Node $node, $related, $original): CommandInterface
    {
        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($related === null) {
            if ($related === $original) {
                // no changes
                return new Nil();
            }

            if ($original !== null) {
                return $this->deleteOriginal($original);
            }
        }

        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        // store command with mounted context paths
        $rStore = $this->forwardContext(
            $node,
            $this->innerKeys,
            $rStore,
            $rNode,
            $this->outerKeys
        );

        if ($original === null) {
            return $rStore;
        }

        $sequence = new ContextSequence();
        $sequence->addCommand($this->deleteOriginal($original));
        $sequence->addPrimary($rStore);

        return $sequence;
    }

    /**
     * Delete original related entity of no other objects reference to it.
     */
    protected function deleteOriginal(object $original): CommandInterface
    {
        $rNode = $this->getNode($original);

        if ($this->isNullable()) {
            $store = $this->orm->queueStore($original);
            foreach ($this->outerKeys as $oKey) {
                // $store->register($oKey, null, true);
                $rNode->getState()->register($oKey, null, true);
            }
            $rNode->getState()->decClaim();

            return new Condition($store, fn() => !$rNode->getState()->hasClaims());
        }

        // only delete original child when no other objects claim it
        return new Condition($this->orm->queueDelete($original), fn() => !$rNode->getState()->hasClaims());
    }
}
