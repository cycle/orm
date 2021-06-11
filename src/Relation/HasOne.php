<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

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

    public function queuePool(Pool $pool, Tuple $tuple, $related, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($original instanceof ReferenceInterface) {
            if (!$load && $related === $original && !$this->isResolved($original)) {
                return;
            }
            $original = $this->resolve($original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($related === null) {
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            if ($original === null) {
                return;
            }
            $this->deleteChild($pool, $original);
            return;
        }
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        $rNode = $this->getNode($related, +1);
        $this->assertValid($rNode);

        if ($original !== null && $original !== $related) {
            $this->deleteChild($pool, $original);
        }
        $pool->attachStore($related, true, $rNode);
    }

    public function queue(Pool $pool, Tuple $tuple, $related): void
    {
        if ($tuple->task === Tuple::TASK_STORE) {
            $this->queueStore($pool, $tuple, $related);
        } else {
            // todo ?
            $this->queueDelete($pool, $tuple, $related);
        }
    }

    private function queueStore(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        if ($related instanceof ReferenceInterface) {
            if (!$this->isResolved($related)) {
                return;
            }
            $related = $this->resolve($related);
        }

        $rTuple = $pool->offsetGet($related);
        $rNode = $rTuple->node;

        $this->applyChanges($tuple, $rTuple);
        $rNode->setRelationStatus($node->getRole() . ':' . $this->getName(), RelationInterface::STATUS_RESOLVED);
    }
    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->node->register($this->outerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
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
        if ($original !== null) {
            $originNode = $this->getNode($original);
            if ($originNode !== null && $originNode->getStatus() === Node::MANAGED) {
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
                $this->deleteChild($pool, $original);
            }
        }
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        if ($related === $original) {
            return;
        }
        $relatedNode = $this->getNode($related);
        if ($relatedNode === null || $relatedNode->getStatus() !== Node::MANAGED) {
            return;
        }
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
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
}
