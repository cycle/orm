<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

/**
 * Soft-delete scope that protects itself from user-added OR conditions by wrapping
 * already-registered WHERE tokens into a parenthesized group before adding its own
 * filter. With a plain {@see NotDeletedScope} a query like
 *
 *     WHERE id = 1 OR id = 2 AND deleted_at IS NULL
 *
 * is parsed by SQL as
 *
 *     WHERE id = 1 OR (id = 2 AND deleted_at IS NULL)
 *
 * which bypasses the scope on the first OR arm. With wrapWhere() the resulting
 * SQL becomes
 *
 *     WHERE (id = 1 OR id = 2) AND deleted_at IS NULL
 *
 * which keeps the scope effective regardless of what user code added.
 */
class NotDeletedWrappedScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->wrapWhere();
        $query->where('deleted_at', '=', null);
    }
}
