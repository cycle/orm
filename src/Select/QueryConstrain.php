<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

/**
 * Provides the ability to scope query and load necessary relations into the loader.
 */
final class QueryConstrain implements ConstrainInterface
{
    /** @var array */
    private $where = [];

    /** @var array */
    private $orderBy = [];

    /**
     * @param array $where
     * @param array $orderBy
     */
    public function __construct(array $where, array $orderBy = [])
    {
        $this->where = $where;
        $this->orderBy = $orderBy;
    }

    /**
     * @inheritdoc
     */
    public function apply(QueryBuilder $query): void
    {
        $query->where($this->where)->orderBy($this->orderBy);
    }
}
