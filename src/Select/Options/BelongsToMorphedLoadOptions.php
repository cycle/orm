<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\ScopeInterface;

/**
 * Load options for BelongsToMorphed relations.
 *
 * This relation uses a separate loading mechanism (not JoinableLoader),
 * so it only supports a limited set of options.
 */
final class BelongsToMorphedLoadOptions extends LoadOptions
{
    public function __construct(
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
    ) {
        parent::__construct($scope, $minify);
    }
}
