<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadOptions;

/**
 * Relations loader
 *
 * Allows to load relations in bulk for a set of collected entities.
 *
 * @note Don't implement this interface directly. The signature might change in the future.
 *
 * Important behavior:
 * - Relations are loaded using the entity state from the database (heap node data), not runtime changes
 * - Already loaded relations (non-references) will not be overwritten
 */
interface RelationLoaderInterface
{
    /**
     * Define relation to be loaded.
     *
     * @param non-empty-string $relation Relation name
     * @param LoadOptions|array $options Relation loading options
     *
     * @see Select::load() for available options
     */
    public function load(string $relation, LoadOptions|array $options = []): static;

    /**
     * Execute relation loading for all collected entities.
     *
     * Only unresolved relations (lazy references) will be loaded.
     * Relations use database state for loading, ignoring runtime changes to foreign keys.
     */
    public function run(): void;
}
