<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseOne;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides ability to link to the parent object.
 * Will claim branch up to the parent object and it's relations. To disable
 * branch walk-through use RefersTo relation.
 */
class BelongsTo extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        ob_flush();
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($related === null) {
            if (!$this->isNullable()) {
                throw new NullException("Relation {$this} can not be null.");
            }

            if ($original !== null) {
                // reset keys
                $state = $node->getState();
                foreach ($this->innerKeys as $innerKey) {
                    $state->register($innerKey, null, true);
                }
            }
            $node->getState()->setRelation($this->getName(), $related);

            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            // nothing to do
            return;
        }
        if ($related instanceof PromiseOne) {
            if ($related->__loaded()) {
                $related = $related->__resolve();
            }
        }
        if ($related instanceof ReferenceInterface) {
            $scope = $related->__scope();
            if (array_intersect($this->outerKeys, array_keys($scope))) {
                foreach ($this->outerKeys as $i => $outerKey) {
                    $node->register($this->innerKeys[$i], $scope[$outerKey]);
                }
                $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return;
            }
        }
        $rTuple = $pool->offsetGet($related) ?? $pool->attachStore($related, true, null, null, true);

        if ($rTuple->status === Tuple::STATUS_PROCESSED) {
            $this->pullValues($node, $rTuple->node);
            $node->getState()->setRelation($this->getName(), $related);
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }

        if ($rTuple->node === null && $tuple->status > Tuple::STATUS_DEFERRED) {
            $node->getState()->setRelation($this->getName(), $related);
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        }
    }

    private function pullValues(Node $node, Node $related): void
    {
        $changes = $related->getState()->getTransactionData();
        foreach ($this->outerKeys as $i => $outerKey) {
            if (isset($changes[$outerKey])) {
                $node->register($this->innerKeys[$i], $changes[$outerKey]);
            }
        }
    }

    public function queue($entity, Node $node, $related, $original): CommandInterface
    {
        if ($related === null) {
            if (!$this->isNullable()) {
                throw new NullException("Relation {$this} can not be null.");
            }

            if ($original !== null) {
                // reset keys
                foreach ($this->innerKeys as $innerKey) {
                    $node->getState()->register($innerKey, null, true);
                }
            }

            // nothing to do
            return new Nil();
        }

        $rStore = $this->orm->queueStore($related);
        $rNode = $this->getNode($related);
        $this->assertValid($rNode);

        $this->forwardContext($rNode, $this->outerKeys, $store, $node, $this->innerKeys);

        return $rStore;
    }
}
