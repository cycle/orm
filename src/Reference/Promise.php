<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

use Cycle\ORM\Relation\ActiveRelationInterface;

final class Promise implements ReferenceInterface
{
    private ActiveRelationInterface $relation;
    private ReferenceInterface $origin;

    public function __construct(ActiveRelationInterface $relation, ReferenceInterface $origin)
    {
        $this->relation = $relation;
        $this->origin = $origin;
    }

    /**
     * @return null|iterable|object
     */
    public function getCollection()
    {
        if (!$this->origin->hasValue()) {
            $this->resolve();
        }
        return $this->relation->collect($this->origin->getValue());
    }

    public function resolve(): void
    {
        $this->relation->resolve($this->origin, true);
    }

    public function getRole(): string
    {
        return $this->origin->getRole();
    }

    public function getScope(): array
    {
        return $this->origin->getScope();
    }

    public function hasValue(): bool
    {
        return $this->origin->hasValue();
    }

    public function setValue(mixed $value): void
    {
        $this->origin->setValue($value);
    }

    public function getValue(): mixed
    {
        return $this->origin->getValue();
    }
}
