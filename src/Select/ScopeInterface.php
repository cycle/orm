<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

/**
 * Scopes attach extra query criteria to every `Select` for a given entity — typical
 * use cases are soft-delete filtering, tenant isolation, default ordering, or
 * inheritance discriminators.
 *
 * A scope is invoked once per query build, **after** any user-supplied WHERE
 * conditions have already been registered on the underlying SelectQuery. That
 * timing is important: see "Avoiding scope bypass via orWhere" below.
 *
 * The {@see apply()} method receives a {@see QueryBuilder} that proxies the
 * underlying query. For scopes attached to joined loaders the builder forwards
 * `where*` and `wrapWhere()` calls to JOIN ON tokens (`onWhere`/`wrapOnWhere`)
 * rather than the top-level WHERE — the recommended pattern below applies
 * uniformly in both cases.
 *
 * ## Registering a scope
 *
 * Either via schema (applied automatically by `getRepository()`):
 *
 *     Schema::SCOPE => SoftDeleteScope::class,
 *
 * Or per-query on a `Select`:
 *
 *     $select->scope(new SoftDeleteScope());
 *
 * ## Recommended pattern: always call `wrapWhere()` first
 *
 * Start `apply()` with a {@see \Cycle\Database\Query\Traits\WhereTrait::wrapWhere()}
 * call, then add your conditions. This protects the scope from being bypassed by
 * user-supplied `orWhere` (explained below):
 *
 *     final class SoftDeleteScope implements ScopeInterface
 *     {
 *         public function apply(QueryBuilder $query): void
 *         {
 *             $query->wrapWhere();                   // enclose user wheres
 *             $query->where('deleted_at', null);     // scope condition outside
 *         }
 *     }
 *
 * `wrapWhere()` is a no-op when no WHERE tokens have been registered yet, so
 * it is always safe to call.
 *
 * ## Why `wrapWhere()` is needed
 *
 * A plain top-level WHERE added by a scope can be defeated by a user `orWhere`
 * because of SQL operator precedence — AND binds tighter than OR. Without
 * `wrapWhere()`, given a scope that simply does `$query->where('deleted_at', null)`
 * and a user query
 *
 *     $select->scope(new SoftDeleteScope())
 *         ->where('id', 1)
 *         ->orWhere('id', 2);
 *
 * the resulting SQL is
 *
 *     WHERE {id} = 1 OR {id} = 2 AND {deleted_at} IS NULL
 *     ≡ WHERE {id} = 1 OR ({id} = 2 AND {deleted_at} IS NULL)
 *
 * which returns rows matching `id = 1` regardless of `deleted_at` — the scope
 * is silently bypassed on the first OR arm. With the recommended pattern above
 * the same query compiles to
 *
 *     WHERE ({id} = 1 OR {id} = 2) AND {deleted_at} IS NULL
 *
 * and the scope holds regardless of how user code mixes AND/OR.
 *
 * ## Stacking multiple scopes
 *
 * Each scope in a chain can call `wrapWhere()` independently — every layer
 * encloses the previous accumulation, producing
 *
 *     WHERE ((user_wheres) AND scope1_conds) AND scope2_conds
 *
 * which is logically equivalent to `user AND scope1 AND scope2`. Stack scopes
 * via a composite/aggregating scope or by registering them at the appropriate
 * layer of your application.
 *
 * ## Other usage
 *
 * Scopes are not limited to WHERE — `apply()` may also call `orderBy()`,
 * `having()`, `limit()`, etc. on the builder. Loader-aware scopes can use the
 * builder's `resolve()` to translate `relation.column` identifiers to proper
 * SQL aliases.
 */
interface ScopeInterface
{
    /**
     * Apply scope-specific modifications to the query builder.
     *
     * Called during query compilation, after any user-supplied WHERE conditions
     * have been registered on the underlying SelectQuery. See the interface-level
     * docblock for guidance on avoiding scope bypass via {@see QueryBuilder::wrapWhere()}.
     */
    public function apply(QueryBuilder $query): void;
}
