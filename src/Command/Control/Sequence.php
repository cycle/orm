<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\CommandInterface;

/**
 * Wraps multiple commands into one sequence.
 */
class Sequence implements CommandInterface, \IteratorAggregate, \Countable
{
    /** @var CommandInterface[] */
    protected $commands = [];

    /**
     * {@inheritdoc}
     */
    public function addCommand(CommandInterface $command)
    {
        if ($command instanceof Nil) {
            return;
        }

        $this->commands[] = $command;
    }

    public function getCommands()
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
    public function isReady(): bool
    {
        // always ready since check will be delegated to underlying nodes
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function onExecute(callable $closure)
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function onComplete(callable $closure)
    {
        // nothing
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function onRollBack(callable $closure)
    {
        // nothing
    }
}