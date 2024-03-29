<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

interface EntityProviderInterface
{
    /**
     * Get/load entity by unique key/value pair.
     *
     * @template TEntity of object
     *
     * @param class-string<TEntity>|string $role Entity role or class name.
     * @param array $scope KV pair to locate the model, currently only support one pair.
     *
     * @return ($role is class-string<TEntity> ? TEntity|null : object|null)
     */
    public function get(string $role, array $scope, bool $load = true): ?object;
}
