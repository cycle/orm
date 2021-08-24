<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

/**
 * Provides the ability to scope query and load necessary relations into the loader.
 * @final
 */
class QueryScope implements ConstrainInterface
{
    /** @var array */
    private $where;

    /** @var array */
    private $orderBy;

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
