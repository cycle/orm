<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface as CC;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Command\DatabaseCommand;
use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\PromiseOne;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Relation\Traits\PromiseOneTrait;
use Cycle\ORM\Schema;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Variation of belongs-to relation which provides the ability to be self linked. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 */
class RefersTo extends AbstractRelation implements DependencyInterface
{
    use PromiseOneTrait;

    public function newQueue(Pool $pool, Tuple $tuple, $related): void
    {
        ob_flush();
        $node = $tuple->node;
        $original = $node->getRelation($this->getName());

        if ($related === null) {
            if ($original !== null) {
                // reset keys
                $state = $node->getState();
                foreach ($this->innerKeys as $innerKey) {
                    $state->register($innerKey, null, true);
                }
            }
            // $node->getState()->setRelation($this->getName(), $related);

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
        $rTuple = $pool->offsetGet($related);
        if ($rTuple === null) {
            if ($this->isCascade()) {
                // todo: cascade true?
                $rTuple = $pool->attachStore($related, false, null, null, false);
            } elseif ($node->getRelationStatus($this->getName()) === RelationInterface::STATUS_DEFERRED && $tuple->status === Tuple::STATUS_PROPOSED) {
                throw new TransactionException('wtf');
            } else {
                $node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
                return;
            }
        }
        // $rTuple = $pool->offsetGet($related);
        // if ($rTuple === null) {
        //     $pool->attachStore($related, true, null, null, false);
        //     $node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
        //     return;
        // }

        if ($rTuple->status === Tuple::STATUS_PROCESSED) {
            $this->pullValues($node, $rTuple->node);
            // $node->getState()->setRelation($this->getName(), $related);
            $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }

        // if ($rTuple->node === null) {
            if ($tuple->status !== Tuple::STATUS_PREPARING) {
                $node->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
            }
            return;
        // }

        $rNode = $rTuple->node;
        $node->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        $rNode = $rNode ?? $this->getNode($related);
        $this->assertValid($rNode);
        $node->getState()->setRelation($this->getName(), $related);

        $this->pullValues($node, $rNode);
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

    public function queue(object $entity, Node $node, $related, $original): CommandInterface
    {
        // refers-to relation is always nullable (as opposite to belongs-to)
        if ($related === null) {
            if ($original !== null) {
                foreach ($this->innerKeys as $innerKey) {
                    $node->getState()->register($innerKey, null, true);
                }
            }

            return new Nil();
        }

        $rNode = $this->getNode($related);
        $this->assertValid($rNode);

        $returnNil = true;
        // related object exists, we can update key immediately
        foreach ($this->outerKeys as $i => $outerKey) {
            $outerValue = $this->fetchKey($rNode, $outerKey);
            $innerKey = $this->innerKeys[$i];

            if ($outerValue === null) {
                $returnNil = false;
                continue;
            }
            if ($outerValue != $this->fetchKey($node, $innerKey)) {
                $node->getState()->register($innerKey, $outerValue, true);
            }
        }
        if ($returnNil) {
            $this->forwardContext($rNode, $this->outerKeys, $store, $node, $this->innerKeys);
            return new Nil();
        }

        // update parent entity once related instance is able to provide us context key
        $update = new Update(
            $this->getSource($node->getRole())->getDatabase(),
            $this->getSource($node->getRole())->getTable(),
            $node
        );

        $this->forwardContext($rNode, $this->outerKeys, $update, $node, $this->innerKeys);
        if ($store instanceof DatabaseCommand) {
            $update->waitCommand($store);
        }

        // fastest way to identify the entity
        $pk = (array)$this->orm->getSchema()->define($node->getRole(), Schema::PRIMARY_KEY);

        // set where condition for update query
        $this->forwardScope($node, $pk, $update, $pk);

        return $update;
    }
}
