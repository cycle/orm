<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Heap\State;
use Cycle\ORM\Transaction\Pool;
use Cycle\ORM\Transaction\Tuple;

/**
 * @internal
 */
class ShadowHasMany implements ReversedRelationInterface, DependencyInterface
{
    private string $targetContainer;

    /**
     * @param string[] $innerKeys
     * @param string[] $outerKeys
     */
    public function __construct(
        private string $name,
        private string $target,
        private array $innerKeys,
        private array $outerKeys,
    ) {
        $this->targetContainer = $name . ':' . $target;
    }

    /**
     * @return string[]
     */
    public function getInnerKeys(): array
    {
        return $this->innerKeys;
    }

    public function prepare(Pool $pool, Tuple $tuple, mixed $related, bool $load = true): void
    {
        $value = $tuple->state->getRelation($this->getName());
        $tuple->state->setRelation($this->getName(), $value ?? []);
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_PROCESS);
    }

    public function queue(Pool $pool, Tuple $tuple): void
    {
        if ($tuple->status <= Tuple::STATUS_WAITED) {
            return;
        }
        $tuple->state->setRelationStatus($this->getName(), RelationInterface::STATUS_RESOLVED);

        $related = $tuple->state->getRelation($this->getName());

        if (\count($related) === 0) {
            return;
        }

        foreach ($related as $item) {
            $rTuple = $pool->offsetGet($item);
            \assert($rTuple !== null);
            $this->applyChanges($tuple->state, $rTuple->state);
            $rTuple->state->setRelationStatus($this->targetContainer, RelationInterface::STATUS_RESOLVED);
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getTarget(): string
    {
        return $this->target;
    }

    public function isCascade(): bool
    {
        return true;
    }

    private function applyChanges(State $from, State $to): void
    {
        foreach ($this->innerKeys as $i => $innerKey) {
            $to->register($this->outerKeys[$i], $from->getValue($innerKey));
        }
    }
}
