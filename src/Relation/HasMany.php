<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Condition;
use Cycle\ORM\Command\Branch\Sequence;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
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

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        if ($tuple->task === Tuple::TASK_STORE) {
            $this->queueStoreAll($pool, $tuple, $related);
        } else {
            // todo
            // $this->queueDelete($pool, $tuple, $related);
        }
    }

    private function shouldResolveOrigin(ReferenceInterface $original, Tuple $tuple): bool
    {
        if ($this->isResolved($original)) {
            return true;
        }
        if (count(array_intersect(array_keys($tuple->node->getChanges()), $this->innerKeys)) > 0) {
            return true;
        }
        return false;
    }

    private function queueStoreAll(Pool $pool, Tuple $tuple, $related): void
    {
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($original instanceof ReferenceInterface) {
            if (!$this->shouldResolveOrigin($original, $tuple)) {
                return;
            }
            $original = $this->resolve($original);
            // $node->setRelation($this->getName(), $original);
        }
        $changes = $node->getData();

        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        // $isApplyChanges = !in_array($tuple->status, [Tuple::STATUS_WAITED, Tuple::STATUS_PREPARING], true);
        $isApplyChanges = $tuple->status >= Tuple::STATUS_DEFERRED;
        // if (!$isApplyChanges && $tuple->status !== Tuple::STATUS_PREPARING) {
        //     return;
        // }
        // Store $related collection
        $relationName = $node->getRole() . ':' . $this->getName();
        foreach ($related as $item) {
            $rNode = $this->getNode($item, +1);
            $rNode->setRelationStatus($relationName, $isApplyChanges ? RelationInterface::STATUS_RESOLVED : RelationInterface::STATUS_DEFERRED);
            $this->assertValid($rNode);

            if ($isApplyChanges) {
                foreach ($this->innerKeys as $i => $innerKey) {
                    if (isset($changes[$innerKey])) {
                        $rNode->register($this->outerKeys[$i], $changes[$innerKey]);
                    }
                }
            }
            $pool->attachStore($item, true, $rNode);
        }

        // Delete diff
        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $this->deleteChild($pool, $item);
        }
        $node->setRelationStatus($this->getName(), $isApplyChanges ? RelationInterface::STATUS_RESOLVED : RelationInterface::STATUS_DEFERRED);
        // $node->getState()->setRelation($this->getName(), $related);
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

    // private function queueDelete(Pool $pool, Tuple $tuple, $related, ?Node $relatedNode): void
    // {
    //     $node = $tuple->node;
    //     $original = $node->getRelation($this->getName());
    //
    //     if ($original instanceof ReferenceInterface) {
    //         $original = $this->resolve($original);
    //     }
    //
    //     if ($related instanceof ReferenceInterface) {
    //         $related = $this->resolve($related);
    //     }
    //     $resolved = false;
    //     if ($original !== null) {
    //         $originNode = $this->getNode($original);
    //         if ($originNode !== null && $originNode->getStatus() === Node::MANAGED) {
    //             $this->setStatus(RelationInterface::STATUS_DEFERRED);
    //             $this->deleteChild($pool, $original);
    //         } else {
    //             $resolved = true;
    //         }
    //     } else {
    //         $resolved = true;
    //     }
    //     $resolved and $this->setStatus(RelationInterface::STATUS_RESOLVED);
    //     if ($related === $original) {
    //         return;
    //     }
    //     $relatedNode = $relatedNode ?? $this->getNode($related);
    //     if ($relatedNode === null || $relatedNode->getStatus() !== Node::MANAGED) {
    //         return;
    //     }
    //     $this->setStatus(RelationInterface::STATUS_DEFERRED);
    //     $this->deleteChild($pool, $related, $relatedNode);
    // }
    //


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
        foreach ($this->innerKeys as $i => $innerKey) {
            $innerValue = $this->fetchKey($node, $innerKey);
            if ($innerValue === null) {
                return [new ArrayCollection(), null];
            }
            $innerValues[] = $innerValue;
        }

        $p = new PromiseMany(
            $this->orm,
            $this->target,
            array_combine($this->outerKeys, $innerValues),
            $this->schema[Relation::WHERE] ?? []
        );

        return [new CollectionPromise($p), $p];
    }

    public function queue(/*CC $store, */object $entity, Node $node, $related, $original): CommandInterface
    {
        if ($related instanceof ReferenceInterface) {
            $related = $this->resolve($related);
        }

        if ($original instanceof ReferenceInterface) {
            $original = $this->resolve($original);
        }

        $sequence = new Sequence();

        foreach ($related as $item) {
            $sequence->addCommand($this->queueStore($node, $item));
        }

        foreach ($this->calcDeleted($related, $original ?? []) as $item) {
            $sequence->addCommand($this->queueDelete($item));
        }

        return $sequence;
    }

    /**
     * Return objects which are subject of removal.
     */
    protected function calcDeleted(iterable $related, iterable $original): array
    {
        // $related = $this->extract($related);
        $original = $this->extract($original);
        return array_udiff(
            $original ?? [],
            $related,
            static fn(object $a, object $b): int => strcmp(spl_object_hash($a), spl_object_hash($b))
        );
    }

    /**
     * Persist related object.
     */
    protected function queueStore(Node $node, object $related): CC
    {
        $relStore = $this->orm->queueStore($related);
        $relNode = $this->getNode($related, +1);
        $this->assertValid($relNode);

        $this->forwardContext(
            $node,
            $this->innerKeys,
            $relStore,
            $relNode,
            $this->outerKeys
        );

        return $relStore;
    }

    /**
     * Remove one of related objects.
     */
    protected function queueDelete(object $related): CommandInterface
    {
        $rNode = $this->getNode($related);

        if ($this->isNullable()) {
            $store = $this->orm->queueStore($related);
            foreach ($this->outerKeys as $key) {
                $rNode->getState()->register($key, null, true);
            }
            $rNode->getState()->decClaim();

            return new Condition($store, fn() => !$rNode->getState()->hasClaims());
        }

        return new Condition($this->orm->queueDelete($related), static fn() => !$rNode->getState()->hasClaims());
    }
}
