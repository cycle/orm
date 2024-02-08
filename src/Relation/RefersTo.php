<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\Traits\ToOneTrait;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Variation of belongs-to relation which provides the ability to be self linked. Relation can be used
 * to create cyclic references. Relation does not trigger store operation of referenced object!
 *
 * @internal
 */
class RefersTo extends AbstractRelation implements DependencyInterface
{
    use ToOneTrait;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        $this->entityProvider = $orm->getService(EntityProviderInterface::class);

        parent::__construct($orm, $role, $name, $target, $schema);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $node = $tuple->node;
        $tuple->state->setRelation($this->getName(), $related);

        if ($related instanceof ReferenceInterface && $this->resolve($related, false) !== null) {
            $related = $related->getValue();
            $tuple->state->setRelation($this->getName(), $related);
        }
        if ($this->checkNullValue($node, $tuple->state, $related)) {
            return;
        }
        $this->registerWaitingFields($tuple->state, false);
        if ($related instanceof ReferenceInterface) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
            return;
        }

        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);
        $rTuple = $pool->offsetGet($related);
        if ($rTuple === null && $this->isCascade()) {
            $pool->attachStore($related, false, null, null, false);
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $node = $tuple->node;
        $related = $tuple->state->getRelation($this->getName());

        if ($related instanceof ReferenceInterface && ($related->hasValue() || $this->resolve($related, false) !== null)) {
            $related = $related->getValue();
            $tuple->state->setRelation($this->getName(), $related);
        }
        if ($related instanceof ReferenceInterface) {
            $scope = $related->getScope();
            if (array_intersect($this->outerKeys, array_keys($scope))) {
                foreach ($this->outerKeys as $i => $outerKey) {
                    $tuple->state->register($this->innerKeys[$i], $scope[$outerKey]);
                }
                $node->setRelation($this->getName(), $related);
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return;
            }
        }
        if ($this->checkNullValue($tuple->node, $tuple->state, $related)) {
            return;
        }
        $rTuple = $pool->offsetGet($related);
        if ($rTuple === null) {
            if ($this->isCascade()) {
                // todo: cascade true?
                $rTuple = $pool->attachStore($related, false, null, null, false);
            } elseif (
                $tuple->state->getRelationStatus($this->getName()) !== RelationInterface::STATUS_DEFERRED
                || $tuple->status !== Tuple::STATUS_PROPOSED
            ) {
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
                return;
            } else {
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return;
            }
        }

        if ($rTuple->status === Tuple::STATUS_PROCESSED
            || ($rTuple->status > Tuple::STATUS_PREPARING
                && $rTuple->state->getStatus() !== node::NEW
                && \array_intersect($this->outerKeys, $rTuple->state->getWaitingFields()) === [])
        ) {
            $this->pullValues($tuple->state, $rTuple->state);
            $node->setRelation($this->getName(), $related);
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return;
        }

        if ($tuple->status !== Tuple::STATUS_PREPARING) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_DEFERRED);
        }
    }

    private function pullValues(State $state, State $rState): void
    {
        $changes = $rState->getTransactionData();
        foreach ($this->outerKeys as $i => $outerKey) {
            if (isset($changes[$outerKey])) {
                $state->register($this->innerKeys[$i], $changes[$outerKey]);
            }
        }
    }

    private function checkNullValue(Node $node, State $state, mixed $value): bool
    {
        if ($value !== null) {
            return false;
        }
        $original = $node->getRelation($this->getName());
        // Original is not null
        if ($original !== null) {
            // Reset keys
            foreach ($this->innerKeys as $innerKey) {
                $state->register($innerKey, null);
            }
        }

        $node->setRelation($this->getName(), null);
        $state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        return true;
    }
}
