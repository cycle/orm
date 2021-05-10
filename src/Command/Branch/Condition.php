<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\CommandInterface;

/**
 * Execute branch only if condition is met.
 */
final class Condition implements CommandInterface, \IteratorAggregate
{
    private CommandInterface $command;

    /** @var callable */
    private $condition;

    public function __construct(CommandInterface $parent, callable $condition)
    {
        $this->command = $parent;
        $this->condition = $condition;
    }

    public function getIterator(): \Generator
    {
        if (call_user_func($this->condition)) {
            yield $this->command;
        }
    }

    public function isExecuted(): bool
    {
        return $this->command->isExecuted();
    }

    /**
     * @codeCoverageIgnore
     */
    public function isReady(): bool
    {
        // condition is always ready
        return $this->command->isReady();
    }

    /**
     * @codeCoverageIgnore
     */
    public function execute(): void
    {
        // nothing to do
    }

    /**
     * @codeCoverageIgnore
     */
    public function complete(): void
    {
        // nothing to do
    }

    /**
     * @codeCoverageIgnore
     */
    public function rollBack(): void
    {
        // nothing to do
    }
}
