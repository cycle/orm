<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

/**
 * Transaction aggregates set of commands declared by entities and executes them all together.
 */
interface TransactionInterface
{
    /**
     * Execute all nested commands in transaction, if failed - transaction MUST automatically
     * rollback and exception instance MUST be thrown.
     *
     * Transaction will be emptied after the execution.
     *
     * @throws \Throwable
     */
    public function run();
}