<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Transaction\StateInterface;

interface EntityManagerInterface
{
    /**
     * Tells the EntityManager to make an Entity managed and persistent.
     *
     * Entity will be queued up with fixing current state.
     * Entity state changes after adding to the queue will be ignored.
     *
     * Note: The entity will be updated or inserted into the database at transaction
     * run or as a result of the run operation.
     */
    public function persistState(object $entity, bool $cascade = true): self;

    /**
     * Tells the EntityManager to make an Entity managed and persistent with deferred state syncing.
     *
     * Entity will be queued up without fixing current state.
     * Entity state changes will be synced with queued state during run operation.
     *
     * Note: The entity will be updated or inserted into the database at transaction
     * run or as a result of the run operation.
     */
    public function persist(object $entity, bool $cascade = true): self;

    /**
     * Delete an entity.
     *
     * Note: A deleted entity will be removed from the database at transaction
     * run or as a result of the run operation.
     */
    public function delete(object $entity, bool $cascade = true): self;

    /**
     * Sync all changes to entities that have been added to the queue with database.
     *
     * Synchronizes the in-memory state of managed entities with the database.
     */
    public function run(): StateInterface;

    /**
     * Clean state.
     */
    public function clean(): static;
}
