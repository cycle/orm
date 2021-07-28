<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\CommandInterface;
use Spiral\Database\DatabaseInterface;

/**
 * Wraps multiple commands into one sequence.
 */
final class Sequence implements CommandInterface, \IteratorAggregate, \Countable
{
    private ?CommandInterface $primary;

    /** @var CommandInterface[] */
    private array $commands = [];

    public function __construct(CommandInterface $primary = null)
    {
        $this->primary = $primary;
        if ($primary !== null) {
            $this->commands[] = $primary;
        }
    }

    public function getPrimaryCommand(): ?CommandInterface
    {
        return $this->primary;
    }

    public function isExecuted(): bool
    {
        foreach ($this->commands as $command) {
            if (!$command->isExecuted()) {
                return false;
            }
        }
        return true;
    }

    public function isReady(): bool
    {
        // always ready since check will be delegated to underlying nodes
        return true;
    }

    public function addCommand(CommandInterface ...$commands): self
    {
        foreach ($commands as $command) {
            $this->commands[] = $command;
        }
        return $this;
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

    public function getDatabase(): ?DatabaseInterface
    {
        return null;
    }
}
