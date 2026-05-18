<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for ManyToMany relations.
 */
final class ManyToManyLoadOptions extends JoinableLoadOptions
{
    public function __construct(
        /**
         * Defines how to load relation data: within the same query (JOIN) or using a separate query.
         *
         * Example:
         *
         *      method: LoadMethod::OuterQuery    // POSTLOAD: separate SELECT (default for M2M)
         *      method: LoadMethod::SingleQuery   // INLOAD: join into the parent query
         *      method: JoinMethod::InnerJoin     // INNER JOIN (filter only, no eager data)
         *      method: JoinMethod::LeftJoin     // LEFT JOIN
         *      method: null                      // use loader default for this relation
         */
        LoadMethod|JoinMethod|null $method = LoadMethod::OuterQuery,

        /**
         * Scope applied to the relation query.
         * - `true` — use the default scope from the source (default)
         * - `false` — disable scope (e.g. to include soft-deleted rows)
         * - `ScopeInterface` — use a custom scope instance
         *
         * Example:
         *
         *      scope: true                                             // default source scope
         *      scope: false                                            // disable scope entirely
         *      scope: new NotDeletedScope()                            // any ScopeInterface
         *      scope: new QueryScope(['@.level' => ['>=' => 3]],
         *                            ['@.level' => 'ASC'])             // ad-hoc inline scope
         */
        ScopeInterface|bool $scope = true,

        /**
         * When true, loader column aliases will be minified in SQL output.
         * @note Intended for debugging purposes. Use with caution: disabling minification
         *       may cause column name conflicts between different relations.
         */
        bool $minify = true,

        /**
         * Custom table alias for the relation in SQL.
         * Calculated automatically if not set.
         *
         * Example:
         *
         *      as: 'tag'    // SELECT ... FROM tags AS tag
         */
        ?string $as = null,

        /**
         * Use an alias defined by another relation (typically from {@see \Cycle\ORM\Select::with()})
         * as the data source instead of generating a new JOIN or query.
         *
         * Example:
         *
         *      // Reuse the join from with() inside load() — avoids a duplicate JOIN.
         *      // Note: Select::with() takes an array, only Select::load() accepts a DTO.
         *      $select->with('tags', ['as' => 'tag'])
         *             ->load('tags', new ManyToManyLoadOptions(using: 'tag'));
         */
        ?string $using = null,

        /**
         * Additional WHERE conditions for the relation query.
         *
         * Placeholders:
         * - `@.column`   — column of the target (related) table
         * - `@.@.column` — column of the pivot (through) table
         *
         * Supports the same array forms as {@see \Cycle\ORM\Select::where()}: simple
         * equality, operator maps, `Parameter` for IN, logical grouping via "@OR"/"@AND",
         * and raw `Fragment`/`Expression` values.
         *
         * Example:
         *
         *      where: ['@.status' => 'active']                                // target.status = 'active'
         *      where: ['@.level' => ['>=' => 3]]                              // target.level >= 3
         *      where: ['@.level' => ['>=' => 3, '<' => 10]]                   // 3 <= target.level < 10
         *      where: ['@.name' => ['LIKE' => '%john%']]                      // target.name LIKE '%john%'
         *      where: ['@.id' => ['between' => [1, 100]]]                     // target.id BETWEEN 1 AND 100
         *      where: ['@.id' => new Parameter([1, 2, 3])]                    // target.id IN (1, 2, 3)
         *      where: ['@.@.deleted_at' => null]                              // pivot.deleted_at IS NULL
         *      where: ['@.status' => 'active', '@.@.role' => 'editor']        // AND on target + pivot
         *      where: [
         *          "@OR" => [
         *              ['@.status' => 'active'],
         *              ['@.level' => ['>=' => 10]],
         *          ],
         *      ]
         */
        public ?array $where = null,

        /**
         * ORDER BY rules for the relation query.
         *
         * Keys are column names, values are `ASC` or `DESC`.
         * Placeholders:
         * - `@.column`   — column of the target (related) table
         * - `@.@.column` — column of the pivot (through) table
         *
         * Example:
         *
         *      orderBy: ['@.created_at' => 'DESC']                   // by target column
         *      orderBy: ['@.@.position' => 'ASC']                    // by pivot column
         *      orderBy: ['@.@.priority' => 'DESC', '@.name' => 'ASC']
         */
        public ?array $orderBy = null,

        /**
         * Options for the pivot (through) table loader.
         *
         * Accepts loader options applied to the pivot table itself:
         * `method`, `scope`, `minify`, `as`, `using`, `load`.
         *
         * Note: to add WHERE/ORDER BY on pivot columns, use `@.@.column` in
         * {@see self::$where} / {@see self::$orderBy} — not this option.
         *
         * Example:
         *
         *      pivot: ['as' => 'tag_link', 'scope' => false]
         */
        public ?array $pivot = null,

        /**
         * Override the table name for loading related entities.
         * Useful for loading data from archive tables or alternative storage.
         *
         * Example:
         *
         *      table: 'tag_archive'    // load from `tag_archive` instead of the schema-defined table
         *
         * @var non-empty-string|null
         */
        ?string $table = null,
    ) {
        parent::__construct($method, $scope, $minify, $as, $using, $table);
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        $this->where === null or $result['where'] = $this->where;
        $this->orderBy === null or $result['orderBy'] = $this->orderBy;
        $this->pivot === null or $result['pivot'] = $this->pivot;
        return $result;
    }
}
