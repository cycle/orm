<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

/**
 * Provides the ability to scope query and load necessary relations into the loader.
 */
final class QueryScope implements ScopeInterface
{
    public function __construct(
        private array $where,
        private array $orderBy = [],
    ) {}

    public function apply(QueryBuilder $query): void
    {
        $query->where($this->where)->orderBy($this->orderBy);
    }
}
