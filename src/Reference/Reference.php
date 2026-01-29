<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

class Reference implements ReferenceInterface
{
    private bool $loaded = false;
    private mixed $value;

    public function __construct(
        protected string $role,
        protected array $scope,
    ) {}

    final public function getRole(): string
    {
        return $this->role;
    }

    final public function getScope(): array
    {
        return $this->scope;
    }

    final public function hasValue(): bool
    {
        return $this->loaded;
    }

    final public function setValue(mixed $value): void
    {
        $this->loaded = true;
        $this->value = $value;
    }

    final public function getValue(): mixed
    {
        return $this->value;
    }
}
