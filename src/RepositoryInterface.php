<?php

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * Defines ability to locate entities based on scope parameters.
 */
interface RepositoryInterface
{
    /**
     * Find entity by the primary key value or return null.
     */
    public function findByPK(mixed $id): ?object;

    /**
     * Find entity using given scope (where).
     */
    public function findOne(array $scope = []): ?object;

    /**
     * Find multiple entities using given scope and sort options.
     */
    public function findAll(array $scope = []): iterable;
}
