<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation\Traits\ToOneTrait;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * Provides ability to link to the parent object.
 * Will claim branch up to the parent object and it's relations. To disable
 * branch walk-through use RefersTo relation.
 *
 * @internal
 */
class BelongsTo extends AbstractRelation implements DependencyInterface
{
    use ToOneTrait;

    public function __construct(ORMInterface $orm, string $role, string $name, string $target, array $schema)
    {
        $this->entityProvider = $orm->getService(EntityProviderInterface::class);

        parent::__construct($orm, $role, $name, $target, $schema);
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $state = $tuple->state;

        $relName = $this->getName();
        if ($state->hasRelation($relName)) {
            $prefill = $state->getRelation($relName);
            $nodeValue = $tuple->node->getRelation($relName);
            if ($nodeValue === $related) {
                $related = $prefill;
            }
        }
        $state->setRelation($relName, $related);

        if ($related === null) {
            $this->setNullFromRelated($tuple, true);
            return;
        }
        $this->registerWaitingFields($tuple->state);
        if ($related instanceof ReferenceInterface && $this->resolve($related, false) !== null) {
            $related = $related->getValue();
            $tuple->state->setRelation($relName, $related);
        }

        $tuple->state->setRelationStatus($relName, RelationInterface::STATUS_PROCESS);
        if ($related instanceof ReferenceInterface) {
            return;
        }
        $rTuple = $pool->offsetGet($related);
        if ($rTuple === null) {
            $pool->attachStore($related, $this->isCascade(), null, null, false);
        }
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        $state = $tuple->state;
        $related = $state->getRelation($this->getName());

        if ($related instanceof ReferenceInterface && $related->hasValue()) {
            $related = $related->getValue();
            $state->setRelation($this->getName(), $related);
        }
        if ($related === null) {
            $this->setNullFromRelated($tuple, false);
            return;
        }
        if ($related instanceof ReferenceInterface) {
            $scope = $related->getScope();
            if (array_intersect($this->outerKeys, array_keys($scope))) {
                foreach ($this->outerKeys as $i => $outerKey) {
                    $state->register($this->innerKeys[$i], $scope[$outerKey]);
                }
                $state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return;
            }
            if ($tuple->status >= Tuple::STATUS_WAITED) {
                $state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            }
            return;
        }
        /** @var Tuple $rTuple */
        $rTuple = $pool->offsetGet($related);

        if ($this->shouldPull($tuple, $rTuple)) {
            $this->pullValues($state, $rTuple->state);
            $state->setRelation($this->getName(), $related);
            $state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        }
    }

    private function shouldPull(Tuple $tuple, Tuple $rTuple): bool
    {
        $minStatus = Tuple::STATUS_PREPROCESSED;
        if ($this->inversion !== null) {
            $relName = $this->getTargetRelationName();
            if ($rTuple->state->getRelationStatus($relName) === RelationInterface::STATUS_RESOLVED) {
                $minStatus = Tuple::STATUS_DEFERRED;
            }
        }
        if ($rTuple->status < $minStatus) {
            return false;
        }

        // Check bidirected relation: when related entity has been removed from HasSome relation
        $oldData = $tuple->node->getData();
        $newData = $rTuple->state->getTransactionData();
        $current = $tuple->state->getData();
        $noChanges = true;
        $toReference = [];
        foreach ($this->outerKeys as $i => $outerKey) {
            $innerKey = $this->innerKeys[$i];
            if (!array_key_exists($innerKey, $oldData) || $oldData[$innerKey] !== $newData[$outerKey]) {
                return true;
            }
            $toReference[$outerKey] = $current[$innerKey];
            $noChanges = $noChanges && Node::compare($current[$innerKey], $oldData[$innerKey]) === 0;
        }
        // If no changes
        if ($noChanges) {
            $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
            return false;
        }
        // Nullable relation and null values
        if ($this->isNullable()) {
            $isNull = true;
            foreach ($this->innerKeys as $innerKey) {
                if (!array_key_exists($innerKey, $current) || $current[$innerKey] !== null) {
                    $isNull = false;
                    break;
                }
            }
            if ($isNull) {
                $tuple->state->setRelation($this->getName(), null);
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
                return false;
            }
        }
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        $reference = new Reference($this->target, $toReference);
        $tuple->state->setRelation(
            $this->getName(),
            $this->resolve($reference, false) ?? $reference,
        );

        return false;
    }

    private function pullValues(State $state, State $related): void
    {
        $changes = $related->getTransactionData();
        foreach ($this->outerKeys as $i => $outerKey) {
            if (isset($changes[$outerKey])) {
                $state->register($this->innerKeys[$i], $changes[$outerKey]);
            }
        }
    }

    private function checkNullValuePossibility(Tuple $tuple): bool
    {
        if ($tuple->status < Tuple::STATUS_WAITED) {
            return true;
        }

        if ($tuple->status < Tuple::STATUS_PREPROCESSED
            && \array_intersect($this->innerKeys, $tuple->state->getWaitingFields(false)) !== []
        ) {
            return true;
        }
        // Check
        $values = [];
        $data = $tuple->state->getData();
        foreach ($this->innerKeys as $i => $innerKey) {
            if (!isset($data[$innerKey])) {
                return false;
            }
            $values[$this->outerKeys[$i]] = $data[$innerKey];
        }

        $tuple->state->setRelation($this->getName(), new Reference($this->target, $values));
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
        return true;
    }

    private function setNullFromRelated(Tuple $tuple, bool $isPreparing): void
    {
        $state = $tuple->state;
        $node = $tuple->node;
        if (!$this->isNullable()) {
            if ($isPreparing) {
                // set null unchanged fields
                $changes = $state->getChanges();
                foreach ($this->innerKeys as $innerKey) {
                    if (!isset($changes[$innerKey])) {
                        $state->register($innerKey, null);
                        // Field must be filled
                        $state->waitField($innerKey, true);
                    }
                }
                $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);
            } elseif (!$this->checkNullValuePossibility($tuple)) {
                throw new NullException(sprintf('Relation `%s`.%s can not be null.', $node->getRole(), (string)$this));
            }
            return;
        }

        $original = $node->getRelation($this->getName());
        if ($original !== null) {
            // reset keys
            foreach ($this->innerKeys as $innerKey) {
                $state->register($innerKey, null);
            }
        }
        $state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);
    }
}
