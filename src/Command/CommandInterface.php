<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\Database\DatabaseInterface;

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

    public function getDatabase(): ?DatabaseInterface;
}
