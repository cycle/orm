<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Special;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\StoreCommand;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\Database\DatabaseInterface;

/**
 * Wraps multiple commands and merge into one.
 */
final class MergeCommand implements CommandInterface, \IteratorAggregate, \Countable
{
    private StoreCommandInterface $primary;

    /** @var CommandInterface[] */
    private array $commands = [];

    /**
     * @param StoreCommandInterface $primary
     */
    public function __construct(CommandInterface $primary)
    {
        if ($primary instanceof Sequence || $primary instanceof self) {
            $primary = $primary->getPrimaryCommand();
        }
        if (!$primary instanceof StoreCommandInterface) {
            throw new \InvalidArgumentException(\sprintf(
                'Parameter `$primary` must be instance of %s.',
                StoreCommandInterface::class,
            ));
        }
        $this->primary = $primary;
    }

    public function getPrimaryCommand(): StoreCommandInterface
    {
        return $this->primary;
    }

    public function isExecuted(): bool
    {
        return $this->primary->isExecuted();
    }

    public function isReady(): bool
    {
        return $this->primary->isReady();
    }

    public function addCommand(StoreCommandInterface $command): void
    {
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
        // Build primary command
        foreach ($this->commands as $command) {
            if (!$command instanceof StoreCommand || $command->getDatabase() !== $this->primary->getDatabase()) {
                continue;
            }
            foreach ($command->getStoreData() as $column => $value) {
                $this->primary->registerColumn($column, $value);
            }
            $command->setDatabase(null);
        }
        yield $this->primary;
        yield from $this->commands;
    }

    public function count(): int
    {
        return \count($this->commands);
    }

    public function execute(): void
    {
        // nothing
    }

    public function getDatabase(): ?DatabaseInterface
    {
        return $this->primary->getDatabase();
    }
}
