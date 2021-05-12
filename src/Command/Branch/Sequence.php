<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\CommandInterface;

/**
 * Wraps multiple commands into one sequence.
 */
final class Sequence implements CommandInterface, \IteratorAggregate, \Countable
{
    /** @var CommandInterface[] */
    private array $commands = [];

    public function isExecuted(): bool
    {
        return false;
    }

    public function isReady(): bool
    {
        // always ready since check will be delegated to underlying nodes
        return true;
    }

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

    public function count(): int
    {
        return count($this->commands);
    }

    public function execute(): void
    {
        // nothing
    }

    public function complete(): void
    {
        // nothing
    }

    public function rollBack(): void
    {
        // nothing
    }
}
