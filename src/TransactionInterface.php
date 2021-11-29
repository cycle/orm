<?php

declare(strict_types=1);

namespace Cycle\ORM;

/**
 * Transaction aggregates set of commands declared by entities and executes them all together.
 *
 * @deprecated since 2.0 use {@see \Cycle\ORM\EntityManagerInterface}
 */
interface TransactionInterface
{
    // how to store/delete entity
    public const MODE_CASCADE = 0;
    public const MODE_ENTITY_ONLY = 1;

    /**
     * Persist the entity.
     */
    public function persist(object $entity, int $mode = self::MODE_CASCADE): self;

    /**
     * Delete entity from the database.
     */
    public function delete(object $entity, int $mode = self::MODE_CASCADE): self;

    /**
     * Execute all nested commands in transaction, if failed - transaction MUST automatically
     * rollback and exception instance MUST be thrown.
     *
     * Attention, Transaction is clean after this invocation, you must assemble new transaction to retry.
     *
     * @throws \Throwable
     */
    public function run(): void;
}
