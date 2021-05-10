<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Select;

/**
 * Repository provides ability to load entities and construct queries.
 */
class Repository implements RepositoryInterface
{
    protected Select $select;

    /**
     * Create repository linked to one specific selector.
     */
    public function __construct(Select $select)
    {
        $this->select = $select;
    }

    /**
     * Repositories are always immutable by default.
     */
    public function __clone()
    {
        $this->select = clone $this->select;
    }

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
     */
    public function select(): Select
    {
        return clone $this->select;
    }
}
