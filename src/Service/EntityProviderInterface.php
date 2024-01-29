<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

interface EntityProviderInterface
{
    /**
     * Get/load entity by unique key/value pair.
     *
     * @template TEntity
     *
     * @param class-string<TEntity>|string $role Entity role or class name.
     * @param array $scope KV pair to locate the model, currently only support one pair.
     *
     * @psalm-return ($role is class-string ? TEntity : object)|null
     */
    public function get(string $role, array $scope, bool $load = true): ?object;
}
