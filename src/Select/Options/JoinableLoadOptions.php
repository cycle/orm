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
         */
        public LoadMethod|JoinMethod|null $method = null,

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
        public ?string $as = null,

        /**
         * Use an alias defined by another relation (typically from {@see \Cycle\ORM\Select::with()})
         * as the data source instead of generating a new JOIN or query.
         */
        public ?string $using = null,
    ) {
        parent::__construct($scope, $minify);
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
