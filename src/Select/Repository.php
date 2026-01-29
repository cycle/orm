<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Select;

/**
 * Repository provides ability to load entities and construct queries.
 *
 * @template TEntity of object
 *
 * @implements RepositoryInterface<TEntity>
 */
class Repository implements RepositoryInterface
{
    /**
     * Create repository linked to one specific selector.
     *
     * @param Select<TEntity> $select
     */
    public function __construct(
        /** @readonly */
        protected Select $select,
    ) {}

    public function findByPK($id): ?object
    {
        return $this->select()->wherePK($id)->fetchOne();
    }

    public function findOne(array $scope = []): ?object
    {
        return $this->select()->fetchOne($scope);
    }

    public function findAll(array $scope = [], array $orderBy = []): iterable
    {
        return $this->select()->where($scope)->orderBy($orderBy)->fetchAll();
    }

    /**
     * Get selector associated with the repository.
     *
     * @return Select<TEntity>
     */
    public function select(): Select
    {
        return clone $this->select;
    }

    public function forUpdate(): static
    {
        $repository = clone $this;
        $repository->select->forUpdate();

        return $repository;
    }

    /**
     * Repositories are always immutable by default.
     */
    public function __clone()
    {
        $this->select = clone $this->select;
    }
}
