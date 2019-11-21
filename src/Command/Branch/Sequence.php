<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\CommandInterface;

/**
 * Wraps multiple commands into one sequence.
 */
final class Sequence implements CommandInterface, \IteratorAggregate, \Countable
{
    /** @var CommandInterface[] */
    protected $commands = [];

    /**
     * @inheritdoc
     */
    public function isExecuted(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        // always ready since check will be delegated to underlying nodes
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function addCommand(CommandInterface $command): void
    {
        if ($command instanceof Nil) {
            return;
        }

        $this->commands[] = $command;
    }

    /**
     * Get array of underlying commands.
     *
     * @return CommandInterface[]
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Generator
    {
        foreach ($this->commands as $command) {
            if ($command instanceof \Traversable) {
                yield from $command;
                continue;
            }

            yield $command;
        }
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->commands);
    }

    /**
     * {@inheritdoc}
     */
    public function execute(): void
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
    public function complete(): void
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack(): void
    {
        // nothing
    }
}
