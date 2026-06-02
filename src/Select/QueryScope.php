<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

/**
 * A ready-made {@see ScopeInterface} implementation that applies a set of WHERE
 * conditions and an ORDER BY rule to the query.
 *
 *     // Only active records, newest first
 *     new QueryScope(['status' => 'active'], ['created_at' => 'DESC']);
 *
 * ## Protecting the scope from `orWhere` bypass
 *
 * By default the conditions are added at the top level of the query. Because SQL
 * binds AND tighter than OR, a user-supplied `orWhere` can bypass the scope:
 *
 *     // scope: WHERE status = 'active'
 *     $select->scope(new QueryScope(['status' => 'active']))
 *         ->where('id', 1)
 *         ->orWhere('id', 2);
 *     // WHERE id = 1 OR id = 2 AND status = 'active'
 *     //   ≡ WHERE id = 1 OR (id = 2 AND status = 'active')  ← scope leaks on first arm
 *
 * Pass `wrapWhere: true` to enclose the already-registered conditions in a
 * parenthesized group before adding the scope's own, keeping it effective
 * regardless of how user code mixes AND/OR:
 *
 *     $select->scope(new QueryScope(['status' => 'active'], wrapWhere: true))
 *         ->where('id', 1)
 *         ->orWhere('id', 2);
 *     // WHERE (id = 1 OR id = 2) AND status = 'active'
 *
 * The wrap is applied via {@see QueryBuilder::wrapWhere()}, which is a no-op when
 * no conditions have been registered yet. For scopes attached to joined relation
 * loaders the call is automatically forwarded to the JOIN's ON tokens. Passing an
 * empty `$where` together with `wrapWhere: true` is still meaningful — it protects
 * user conditions without adding any of its own.
 *
 * See {@see ScopeInterface} for the full discussion of scope bypass.
 */
final class QueryScope implements ScopeInterface
{
    /**
     * @param array $where Conditions to apply, in {@see QueryBuilder::where()} array syntax.
     * @param array $orderBy ORDER BY rules, in {@see QueryBuilder::orderBy()} array syntax.
     * @param bool $wrapWhere When true, wrap already-registered conditions in a nested
     *        AND-group before adding this scope's conditions (protects against `orWhere`
     *        bypass). Defaults to false to preserve backwards-compatible behavior.
     */
    public function __construct(
        private readonly array $where,
        private readonly array $orderBy = [],
        private readonly bool $wrapWhere = false,
    ) {}

    public function apply(QueryBuilder $query): void
    {
        if ($this->wrapWhere) {
            $query->wrapWhere();
        }

        $query->where($this->where)->orderBy($this->orderBy);
    }
}
