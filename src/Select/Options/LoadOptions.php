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
        public ScopeInterface|bool $scope = true,

        /**
         * When true, loader column aliases will be minified in SQL output.
         * @note Intended for debugging purposes. Use with caution: disabling minification
         *       may cause column name conflicts between different relations.
         */
        public bool $minify = true,

        /**
         * Override the table name for loading related entities.
         * Useful for loading data from archive tables or alternative storage.
         *
         * Example:
         *
         *      table: 'user_archive'    // load from `user_archive` instead of the schema-defined table
         *
         * @var non-empty-string|null
         */
        public ?string $table = null,
    ) {}

    public function toArray(): array
    {
        $result = [
            'scope' => $this->scope,
            'minify' => $this->minify,
        ];
        $this->table === null or $result['table'] = $this->table;
        return $result;
    }
}
