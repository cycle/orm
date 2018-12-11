<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

/**
 * Transaction aggregates set of commands declared by entities and executes them all together.
 */
interface TransactionInterface
{
    /**
     * Persist the entity.
     *
     * @param object $entity
     */
    public function store($entity);

    /**
     * Delete entity from the database.
     *
     * @param object $entity
     */
    public function delete($entity);

    /**
     * Execute all nested commands in transaction, if failed - transaction MUST automatically
     * rollback and exception instance MUST be thrown.
     *
     * @throws \Throwable
     */
    public function run();
}