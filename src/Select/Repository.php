<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\RepositoryInterface;
use Cycle\ORM\Select;

/**
 * Repository provides ability to load entities and construct queries.
 */
class Repository implements RepositoryInterface
{
    /** @var Select */
    protected $select;

    /**
     * Create repository linked to one specific selector.
     *
     * @param Select $select
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

    /**
     * @inheritdoc
     */
    public function findByPK($id)
    {
        return $this->select()->wherePK($id)->fetchOne();
    }

    /**
     * @inheritdoc
     */
    public function findOne(array $scope = [])
    {
        return $this->select()->where($scope)->limit(1)->fetchOne();
    }

    /**
     * @inheritdoc
     */
    public function findAll(array $scope = [], array $orderBy = []): iterable
    {
        return $this->select()->where($scope)->orderBy($orderBy)->fetchAll();
    }

    /**
     * Get selector associated with the repository.
     *
     * @return Select|iterable
     */
    public function select(): Select
    {
        return clone $this->select;
    }
}
