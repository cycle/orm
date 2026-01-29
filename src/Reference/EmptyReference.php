<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

final class EmptyReference implements ReferenceInterface
{
    public function __construct(
        private string $role,
        private mixed $value,
    ) {}

    public function getRole(): string
    {
        return $this->role;
    }

    public function getScope(): array
    {
        throw new \RuntimeException();
    }

    public function hasValue(): bool
    {
        return true;
    }

    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}
