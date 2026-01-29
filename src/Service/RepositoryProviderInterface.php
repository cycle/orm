<?php

declare(strict_types=1);

namespace Cycle\ORM\Service;

use Cycle\ORM\RepositoryInterface;

interface RepositoryProviderInterface
{
    /**
     * @template TEntity of object
     *
     * Get repository associated with given entity role.
     *
     * @param class-string<TEntity>|non-empty-string $entity
     * @return ($entity is class-string<TEntity> ? RepositoryInterface<TEntity> : RepositoryInterface<object>)
     */
    public function getRepository(string $entity): RepositoryInterface;
}
