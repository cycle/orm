<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

class Reference implements ReferenceInterface
{
    protected string $role;

    protected array $scope;

    private bool $loaded = false;

    /** @var mixed */
    private $value;

    public function __construct(string $role, array $scope)
    {
        $this->role = $role;
        $this->scope = $scope;
    }

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

    final public function setValue($value): void
    {
        $this->loaded = true;
        $this->value = $value;
    }

    final public function getValue()
    {
        return $this->value;
    }
}
