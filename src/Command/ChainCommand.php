<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

use Spiral\ORM\Exception\CommandException;

/**
 * Manages chain of nested commands with one "leading" command. Provide ability to change
 * context for the leading command.
 *
 * Leading command can be in a middle of the chain!
 */
class ChainCommand implements \IteratorAggregate, CommandPromiseInterface
{
    /** @var CommandPromiseInterface */
    private $target;

    /** @var CommandInterface[] */
    private $commands = [];

    /**
     * {@inheritdoc}
     */
    public function addCommand(CommandInterface $command)
    {
        if ($command instanceof NullCommand) {
            return;
        }

        $this->commands[] = $command;
    }

    /**
     * @param CommandPromiseInterface $command
     */
    public function addTargetCommand(CommandPromiseInterface $command)
    {
        $this->commands[] = $command;
        $this->target = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function isEmpty(): bool
    {
        return $this->getTarget()->isEmpty();
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->getTarget()->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function addContext(string $name, $value)
    {
        $this->getTarget()->addContext($name, $value);
    }

    /**
     * @return mixed|null
     */
    public function getPrimaryKey()
    {
        return $this->getTarget()->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Generator
    {
        foreach ($this->commands as $command) {
            if ($command instanceof \Traversable) {
                yield from $command;
            }

            yield $command;
        }
    }

    /**
     * Closure to be called after command executing.
     *
     * @param callable $closure
     */
    final public function onExecute(callable $closure)
    {
        $this->getTarget()->onExecute($closure);
    }

    /**
     * To be called after parent transaction been commited.
     *
     * @param callable $closure
     */
    final public function onComplete(callable $closure)
    {
        $this->getTarget()->onComplete($closure);
    }

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    final public function onRollBack(callable $closure)
    {
        $this->getTarget()->onRollBack($closure);
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
     * @return CommandPromiseInterface
     */
    protected function getTarget(): CommandPromiseInterface
    {
        if (empty($this->target)) {
            throw new CommandException("Chain target command is not set.");
        }

        return $this->target;
    }
}