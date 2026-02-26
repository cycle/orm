<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for MorphedHasOne relations.
 *
 * Shares the same base options as HasOne since MorphedHasOneLoader extends HasOneLoader.
 * Exists as a separate class to allow adding morphed-specific options in the future.
 */
final class MorphedHasOneLoadOptions extends JoinableLoadOptions
{
    public function __construct(
        /**
         * Defines how to load relation data: within the same query (JOIN) or using a separate query.
         */
        LoadMethod|JoinMethod|null $method = LoadMethod::SingleQuery,

        /**
         * Scope applied to the relation query.
         * - `true` — use the default scope from the source (default)
         * - `false` — disable scope
         * - `ScopeInterface` — use a custom scope instance
         */
        ScopeInterface|bool $scope = true,

        /**
         * When true, loader column aliases will be minified in SQL output.
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
        return [
            'where' => $this->where,
            'orderBy' => $this->orderBy,
        ] + parent::toArray();
    }
}
