<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for HasOne relations.
 */
final class HasOneLoadOptions extends JoinableLoadOptions
{
    public function __construct(
        /**
         * Defines how to load relation data: within the same query (JOIN) or using a separate query.
         *
         * Example:
         *
         *      method: LoadMethod::SingleQuery   // INLOAD: join into the parent query (default for HasOne)
         *      method: LoadMethod::OuterQuery    // POSTLOAD: separate SELECT
         *      method: JoinMethod::InnerJoin     // INNER JOIN (filter only, no eager data)
         *      method: JoinMethod::LeftJoin      // LEFT JOIN
         *      method: null                      // use loader default for this relation
         */
        LoadMethod|JoinMethod|null $method = LoadMethod::SingleQuery,

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
         *      scope: new QueryScope(['@.active' => true])             // ad-hoc inline scope
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
         *      as: 'profile'    // SELECT ... FROM profiles AS profile
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
         *      $select->with('profile', ['as' => 'profile'])
         *             ->load('profile', new HasOneLoadOptions(using: 'profile'));
         */
        ?string $using = null,

        /**
         * Additional WHERE conditions for the relation query.
         *
         * Use the `@` placeholder to refer to the target table alias.
         * Supports the same array forms as {@see \Cycle\ORM\Select::where()}: simple
         * equality, operator maps, `Parameter` for IN, logical grouping via "@OR"/"@AND",
         * and raw `Fragment`/`Expression` values.
         *
         * Example:
         *
         *      where: ['@.active' => true]                           // active = true
         *      where: ['@.score' => ['>=' => 50]]                    // score >= 50
         *      where: ['@.email' => ['LIKE' => "%@example.com"]]     // email LIKE "%@example.com"
         *      where: ['@.deleted_at' => null]                       // deleted_at IS NULL
         *      where: [
         *          "@OR" => [
         *              ['@.role' => 'admin'],
         *              ['@.score' => ['>=' => 100]],
         *          ],
         *      ]
         */
        public ?array $where = null,

        /**
         * ORDER BY rules for the relation query.
         *
         * Keys are column names (use `@` as a placeholder for the target table alias),
         * values are `ASC` or `DESC`. For HasOne this affects which row is returned
         * when multiple rows could match (only the first is bound).
         *
         * Example:
         *
         *      orderBy: ['@.updated_at' => 'DESC']                   // latest first
         *      orderBy: ['@.priority' => 'DESC', '@.id' => 'ASC']
         */
        public ?array $orderBy = null,

        /**
         * Override the table name for loading related entities.
         * Useful for loading data from archive tables or alternative storage.
         *
         * Example:
         *
         *      table: 'profile_archive'    // load from `profile_archive` instead of the schema-defined table
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
        return $result;
    }
}
