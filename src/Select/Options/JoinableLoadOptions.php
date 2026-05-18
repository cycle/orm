<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for relations that support JoinableLoader features:
 * loading method selection, table aliasing, and reusing joins.
 */
class JoinableLoadOptions extends LoadOptions
{
    public function __construct(
        /**
         * Defines how to load relation data: within the same query (JOIN) or using a separate query.
         *
         * Example:
         *
         *      method: LoadMethod::SingleQuery   // INLOAD: join into the parent query
         *      method: LoadMethod::OuterQuery    // POSTLOAD: separate SELECT
         *      method: JoinMethod::InnerJoin     // INNER JOIN (filter only, no eager data)
         *      method: JoinMethod::LeftJoin      // LEFT JOIN
         *      method: null                      // use loader default for this relation
         */
        public LoadMethod|JoinMethod|null $method = null,

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
         *      as: 'rel'    // SELECT ... FROM <table> AS rel
         */
        public ?string $as = null,

        /**
         * Use an alias defined by another relation (typically from {@see \Cycle\ORM\Select::with()})
         * as the data source instead of generating a new JOIN or query.
         *
         * Example:
         *
         *      // Reuse the join from with() inside load() — avoids a duplicate JOIN.
         *      // Note: Select::with() takes an array, only Select::load() accepts a DTO.
         *      $select->with('rel', ['as' => 'rel'])
         *             ->load('rel', new JoinableLoadOptions(using: 'rel'));
         */
        public ?string $using = null,

        /**
         * Override the table name for loading related entities.
         *
         * Example:
         *
         *      table: 'rel_archive'    // load from an alternative table
         *
         * @var non-empty-string|null
         */
        ?string $table = null,
    ) {
        parent::__construct($scope, $minify, $table);
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        $this->method === null or $result['method'] = $this->method->value;
        $this->as === null or $result['as'] = $this->as;
        $this->using === null or $result['using'] = $this->using;
        return $result;
    }
}
