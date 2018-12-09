<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Entity;

use Spiral\ORM\Selector;

/**
 * Repository provides ability to load entities and construct queries.
 */
class Source implements SourceInterface
{
    /** @var Selector */
    private $selector;

    /**
     * Create repository linked to one specific selector.
     *
     * @param Selector $selector
     */
    public function __construct(Selector $selector)
    {
        $this->selector = $selector;
    }

    /**
     * @inheritdoc
     */
    public function findByPK($id)
    {
        return $this->find()->wherePK($id)->fetchOne();
    }

    /**
     * @inheritdoc
     */
    public function findOne(array $scope = [])
    {
        return $this->find($scope)->fetchOne();
    }

    /**
     * @inheritdoc
     */
    public function findAll(array $scope = [], array $orderBy = []): iterable
    {
        return $this->find($scope)->orderBy($orderBy)->fetchAll();
    }

    /**
     * @param array $where
     * @return Selector|iterable
     */
    public function find(array $where = []): Selector
    {
        return (clone $this->selector)->where($where);
    }

    /**
     * Create new version of repository with scope defined by
     * closure function.
     *
     * @param callable $scope
     * @return Source
     */
    public function withScope(callable $scope): SourceInterface
    {
        $repository = clone $this;
        call_user_func($scope, $repository->selector);

        return $repository;
    }

    /**
     * Repositories are always immutable by default.
     */
    public function __clone()
    {
        $this->selector = clone $this->selector;
    }
}