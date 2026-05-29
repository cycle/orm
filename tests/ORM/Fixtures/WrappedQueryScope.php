<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

/**
 * Like {@see \Cycle\ORM\Select\QueryScope}, but calls wrapWhere() before adding its
 * conditions — protects the scope's WHERE/ON tokens from being bypassed by a later
 * `orWhere`/`orOnWhere`. For joined-loader scopes, wrapWhere() is forwarded to
 * wrapOnWhere() by {@see QueryBuilder::targetFunc()}.
 */
final class WrappedQueryScope implements ScopeInterface
{
    public function __construct(
        private array $where,
        private array $orderBy = [],
    ) {}

    public function apply(QueryBuilder $query): void
    {
        $query->wrapWhere();
        $query->where($this->where)->orderBy($this->orderBy);
    }
}
