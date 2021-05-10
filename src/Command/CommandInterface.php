<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

/**
 * Represent one or multiple operations in transaction.
 *
 * Attention, ALL commands are flatten before execution to extract sub commands, implement
 * Traversable interface to let transaction to flatten command.
 */
interface CommandInterface
{
    /**
     * Must return true when command is ready for the execution. UnitOfWork will throw
     * an exception if any of the command will stuck in non ready state.
     */
    public function isReady(): bool;

    /**
     * Indicates that command has been executed.
     */
    public function isExecuted(): bool;

    /**
     * Executes command.
     */
    public function execute(): void;

    /**
     * Complete command, method to be called when all other commands are already executed and
     * transaction is closed.
     */
    public function complete(): void;

    /**
     * Rollback command or declare that command been rolled back.
     */
    public function rollBack(): void;
}
