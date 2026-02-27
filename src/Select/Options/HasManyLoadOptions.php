<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for HasMany relations.
 */
final class HasManyLoadOptions extends JoinableLoadOptions
{
    public function __construct(
        /**
         * Defines how to load relation data: within the same query (JOIN) or using a separate query.
         */
        LoadMethod|JoinMethod|null $method = LoadMethod::OuterQuery,

        /**
         * Scope applied to the relation query.
         * - `true` — use the default scope from the source (default)
         * - `false` — disable scope
         * - `ScopeInterface` — use a custom scope instance
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
         */
        ?string $as = null,

        /**
         * Use an alias defined by another relation (typically from {@see \Cycle\ORM\Select::with()})
         * as the data source instead of generating a new JOIN or query.
         */
        ?string $using = null,

        /**
         * Additional WHERE conditions for the relation query.
         */
        public ?array $where = null,

        /**
         * ORDER BY rules for the relation query.
         */
        public ?array $orderBy = null,
    ) {
        parent::__construct($method, $scope, $minify, $as, $using);
    }

    public function toArray(): array
    {
        $result = parent::toArray();
        $this->where === null or $result['where'] = $this->where;
        $this->orderBy === null or $result['orderBy'] = $this->orderBy;
        return $result;
    }
}
