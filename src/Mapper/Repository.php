<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Select;

/**
 * Repository provides ability to load entities and construct queries.
 */
class Repository implements RepositoryInterface
{
    /** @var Select */
    protected $selector;

    /**
     * Create repository linked to one specific selector.
     *
     * @param Select $selector
     */
    public function __construct(Select $selector)
    {
        $this->selector = $selector;
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
        return $this->select()->where($scope)->fetchOne();
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
        return clone $this->selector;
    }

    /**
     * Repositories are always immutable by default.
     */
    public function __clone()
    {
        $this->selector = clone $this->selector;
    }
}