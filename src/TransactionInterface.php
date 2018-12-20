<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle;

/**
 * Transaction aggregates set of commands declared by entities and executes them all together.
 */
interface TransactionInterface
{
    // how to store/delete entity
    public const MODE_CASCADE     = 0;
    public const MODE_ENTITY_ONLY = 1;

    /**
     * Persist the entity.
     *
     * @param object $entity
     * @param int    $mode
     */
    public function persist($entity, int $mode = self::MODE_CASCADE);

    /**
     * Delete entity from the database.
     *
     * @param object $entity
     * @param int    $mode
     */
    public function delete($entity, int $mode = self::MODE_CASCADE);

    /**
     * Execute all nested commands in transaction, if failed - transaction MUST automatically
     * rollback and exception instance MUST be thrown.
     *
     * @throws \Throwable
     */
    public function run();
}