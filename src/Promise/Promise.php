<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

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

    public function __role(): string
    {
        return $this->origin->__role();
    }

    public function __scope(): array
    {
        return $this->origin->__scope();
    }

    public function hasValue(): bool
    {
        return $this->origin->hasValue();
    }

    public function setValue($value): void
    {
        $this->origin->setValue($value);
    }

    public function getValue()
    {
        return $this->origin->getValue();
    }
}
