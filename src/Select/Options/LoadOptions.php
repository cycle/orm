<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Base load options shared by all relation types.
 */
class LoadOptions
{
    public function __construct(
        /**
         * Scope applied to the relation query.
         * - `true` — use the default scope from the source (default)
         * - `false` — disable scope
         * - `ScopeInterface` — use a custom scope instance
         */
        public ScopeInterface|bool $scope = true,

        /**
         * When true, loader column aliases will be minified in SQL output.
         * @note Intended for debugging purposes. Use with caution: disabling minification
         *       may cause column name conflicts between different relations.
         */
        public bool $minify = true,
    ) {}

    public function toArray(): array
    {
        return [
            'scope' => $this->scope,
            'minify' => $this->minify,
        ];
    }
}
