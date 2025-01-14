<?php

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * Defines ability to locate entities based on scope parameters.
 *
 * @template TEntity
 */
interface RepositoryInterface
{
    /**
     * Find entity by the primary key value or return null.
     *
     * @return TEntity|null
     */
    public function findByPK(mixed $id): ?object;

    /**
     * Find entity using given scope (where).
     *
     * @return TEntity|null
     */
    public function findOne(array $scope = []): ?object;

    /**
     * Find multiple entities using given scope and sort options.
     *
     * @return iterable<TEntity>
     */
    public function findAll(array $scope = []): iterable;
}
