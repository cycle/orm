<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Exception\CommandException;

/**
 * Wraps the sequence with commands and provides an ability to mock access to the primary command.
 */
final class ContextSequence implements ContextCarrierInterface, \IteratorAggregate, \Countable
{
    private ?ContextCarrierInterface $primary = null;

    /** @var CommandInterface[] */
    private array $commands = [];

    /**
     * Add primary command to the sequence.
     *
     * @param ContextCarrierInterface $command
     */
    public function addPrimary(ContextCarrierInterface $command): void
    {
        $this->addCommand($command);
        $this->primary = $command;
    }

    public function getPrimary(): ContextCarrierInterface
    {
        if (empty($this->primary)) {
            throw new CommandException('Primary sequence command is not set');
        }

        return $this->primary;
    }

    public function waitContext(string $key, bool $required = true): void
    {
        $this->getPrimary()->waitContext($key, $required);
    }

    public function getContext(): array
    {
        return $this->getPrimary()->getContext();
    }

    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = ConsumerInterface::DATA
    ): void {
        $this->getPrimary()->register($key, $value, $fresh, $stream);
    }

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
