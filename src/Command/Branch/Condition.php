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
 * Execute branch only if condition is met.
 */
final class Condition implements CommandInterface, \IteratorAggregate
{
    /** @var CommandInterface */
    private $command;

    /** @var callable */
    private $condition;

    /**
     * @param CommandInterface $parent
     * @param callable         $condition
     */
    public function __construct(CommandInterface $parent, callable $condition)
    {
        $this->command = $parent;
        $this->condition = $condition;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        if (call_user_func($this->condition)) {
            yield $this->command;
        }
    }

    /**
     * @inheritdoc
     */
    public function isExecuted(): bool
    {
        return $this->command->isExecuted();
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function isReady(): bool
    {
        // condition is always ready
        return $this->command->isReady();
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function execute(): void
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function complete(): void
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function rollBack(): void
    {
        // nothing to do
    }
}
