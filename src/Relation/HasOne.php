<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides the ability to own and forward context values to child entity.
 */
class HasOne extends AbstractRelation
{
    use PromiseOneTrait;

    public function prepare(Pool $pool, Tuple $tuple, $entityData, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());
        // $related = $tuple->state->getRelation($this->getName());
        $related = $entityData;
        $tuple->state->setRelation($this->getName(), $related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $this->compareReferences($original, $related)) {
                $original = $related instanceof ReferenceInterface ? $this->resolve($related, false) : $related;
                if ($original === null) {
                    // not found in heap
                    $node->setRelation($this->getName(), $related);
                    $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                    return;
                }
            } else {
                $original = $this->resolve($original, true);
            }
            $node->setRelation($this->getName(), $original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related, true);
            $tuple->state->setRelation($this->getName(), $related);
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

        $rTuple = $pool->attachStore($related, true);
        $this->assertValid($rTuple->node);

        if ($original !== null && $original !== $related) {
            $this->deleteChild($pool, $original);
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        if ($tuple->task !== Tuple::TASK_STORE) {
            return;
        }
        $related = $tuple->state->getRelation($this->getName());
        $tuple->node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        if ($related instanceof ReferenceInterface && !$this->isResolved($related)) {
            return;
        }

        $rTuple = $pool->offsetGet($related);
        $rNode = $rTuple->node;

        $this->applyChanges($tuple, $rTuple);
        $rNode->setRelationStatus($this->getTargetRelationName(), RelationInterface::STATUS_RESOLVED);
    }

    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->node->register($this->outerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
    }

    /**
     * Delete original related entity of no other objects reference to it.
     * @see \Cycle\ORM\Relation\HasMany::deleteChild todo DRY
     */
    private function deleteChild(Pool $pool, object $child): Tuple
    {
        if ($this->isNullable()) {
            $rTuple = $pool->attachStore($child, false);
            foreach ($this->outerKeys as $outerKey) {
                $rTuple->state->register($outerKey, null);
            }
            // todo: is it needed?
            // $rTuple->node->setRelationStatus($this->getTargetRelationName(), RelationInterface::STATUS_RESOLVED);
            return $rTuple;
        }
        return $pool->attachDelete($child, $this->isCascade());
    }
}
