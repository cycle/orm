<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Select;

/**
 * Relations loader
 *
 * Allows to load relations in bulk for a set of collected entities.
 *
 * @note Don't implement this interface directly. The signature might change in the future.
 */
interface RelationLoaderInterface
{
    /**
     * Define relation to be loaded.
     *
     * @param non-empty-string $relation Relation name
     * @param array $options Relation loading options
     *
     * @see Select::load() for available options\
     */
    public function load(string $relation, array $options = []): static;

    /**
     * Execute relation loading for all collected entities.
     */
    public function run(): void;
}
