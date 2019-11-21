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
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Exception\CommandException;

/**
 * Wraps the sequence with commands and provides an ability to mock access to the primary command.
 */
final class ContextSequence implements ContextCarrierInterface, \IteratorAggregate, \Countable
{
    /** @var ContextCarrierInterface */
    protected $primary;

    /** @var CommandInterface[] */
    protected $commands = [];

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

    /**
     * @return ContextCarrierInterface
     */
    public function getPrimary(): ContextCarrierInterface
    {
        if (empty($this->primary)) {
            throw new CommandException('Primary sequence command is not set');
        }

        return $this->primary;
    }

    /**
     * {@inheritdoc}
     */
    public function waitContext(string $key, bool $required = true)
    {
        return $this->getPrimary()->waitContext($key, $required);
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->getPrimary()->getContext();
    }

    /**
     * @inheritdoc
     */
    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = ConsumerInterface::DATA
    ): void {
        $this->getPrimary()->register($key, $value, $fresh, $stream);
    }

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
