<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise;

final class Reference implements ReferenceInterface
{
    private string $role;

    private array $scope;

    public function __construct(string $role, array $scope)
    {
        $this->role = $role;
        $this->scope = $scope;
    }

    public function __role(): string
    {
        return $this->role;
    }

    public function __scope(): array
    {
        return $this->scope;
    }
}
