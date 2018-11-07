<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Command;

/**
 * Represent one or multiple operations in transaction.
 *
 * Attention, ALL commands are flatten before execution to extract sub commands, implement
 * Traversable interface to let transaction to flatten command.
 */
interface CommandInterface
{
    /**
     * Executes command.
     */
    public function execute();

    /**
     * Complete command, method to be called when all other commands are already executed and
     * transaction is closed.
     */
    public function complete();

    /**
     * Rollback command or declare that command been rolled back.
     */
    public function rollBack();

    /**
     * Closure to be called after command executing.
     *
     * @param callable $closure
     */
    public function onExecute(callable $closure);

    /**
     * To be called after parent transaction been committed.
     *
     * @param callable $closure
     */
    public function onComplete(callable $closure);

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    public function onRollBack(callable $closure);
}