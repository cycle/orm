<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Collection\CollectionPromise;
use Cycle\ORM\Promise\PromiseInterface;
use Cycle\ORM\Promise\PromiseMany;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Provides the ability to own the collection of entities.
 */
class HasMany extends AbstractRelation
{
    public function prepare(Pool $pool, Tuple $tuple, bool $load = true): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());
        $related = $tuple->state->getRelation($this->getName());
        $related = $this->extract($related);

        if ($original instanceof ReferenceInterface) {
            if (!$load && $related === $original && !$this->isResolved($original)) {
                return;
            }
            $original = $this->resolve($original);
            $node->setRelation($this->getName(), $original);
        }

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
            $tuple->state->setRelation($this->getName(), $related);
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $this->deleteChild($pool, $item);
        }

        if (count($related) === 0) {
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);

        // $relationName = $this->getTargetRelationName()
        // Store new and existing items
        foreach ($related as $item) {
            $rNode = $this->getNode($item, +1);
            $this->assertValid($rNode);
            $pool->attachStore($item, true, $rNode);
            if ($this->isNullable()) {
                // todo?
                // $rNode->setRelationStatus($relationName, RelationInterface::STATUS_DEFERRED);
            }
        }

    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $related = $tuple->state->getRelation($this->getName());
        if ($tuple->task === Tuple::TASK_STORE) {
            $this->queueStoreAll($pool, $tuple, $this->extract($related));
        } else {
            // todo
            // $this->queueDelete($pool, $tuple, $related);
        }
    }

    private function queueStoreAll(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        if ($related instanceof ReferenceInterface && !$this->isResolved($related)) {
            return;
        }

        $relationName = $this->getTargetRelationName();
        foreach ($related as $item) {
            $rTuple = $pool->offsetGet($item);
            $this->applyChanges($tuple, $rTuple);
            $rTuple->node->setRelationStatus($relationName, RelationInterface::STATUS_RESOLVED);
        }
    }

    protected function applyChanges(Tuple $parentTuple, Tuple $tuple): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $tuple->node->register($this->outerKeys[$i], $parentTuple->state->getValue($innerKey));
        }
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
            // todo relation status
            return $pool->attachStore($child, false, $relatedNode, $relatedNode->getState());
        }

        return $pool->attachDelete($child, $this->isCascade(), $relatedNode);
    }

    /**
     * Init relation state and entity collection.
     */
    public function init(Node $node, array $data): array
    {
        $elements = [];
        foreach ($data as $item) {
            $elements[] = $this->orm->make($this->target, $item, Node::MANAGED);
        }

        return [new ArrayCollection($elements), $elements];
    }

    /**
     * Convert entity data into array.
     *
     * @param mixed $data
     * @return array|PromiseInterface
     */
    public function extract($data)
    {
        if ($data instanceof CollectionPromise && !$data->isInitialized()) {
            return $data->getPromise();
        }

        if ($data instanceof Collection) {
            return $data->toArray();
        }

        return is_array($data) ? $data : [];
    }

    public function initPromise(Node $node): array
    {
        $innerValues = [];
        $nodeData = $node->getData();
        foreach ($this->innerKeys as $innerKey) {
            if (!isset($nodeData[$innerKey])) {
                return [new ArrayCollection(), null];
            }
            $innerValues[] = $nodeData[$innerKey];
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            array_combine($this->outerKeys, $innerValues),
            $this->schema[Relation::WHERE] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    /**
     * Return objects which are subject of removal.
     */
    protected function calcDeleted(iterable $related, iterable $original): array
    {
        // $related = $this->extract($related);
        $original = $this->extract($original);
        if ($original instanceof PromiseInterface) {
            $original = $original->__resolve();
        }
        return array_udiff(
            $original ?? [],
            $related,
            static fn(object $a, object $b): int => strcmp(spl_object_hash($a), spl_object_hash($b))
        );
    }
}
