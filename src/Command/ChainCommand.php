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
 * Manages chain of nested commands with one "parent" command. Provide ability to change
 * context for the leading command.
 *
 * Leading command can be in a middle of the chain!
 */
class ChainCommand implements \IteratorAggregate, ContextualCommandInterface
{
    /** @var ContextualCommandInterface */
    private $parent;

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
     * @param ContextualCommandInterface $command
     */
    public function addParent(ContextualCommandInterface $command)
    {
        $this->commands[] = $command;
        $this->parent = $command;
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return $this->getParent()->getContext();
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(string $name, $value)
    {
        $this->getParent()->setContext($name, $value);
    }

    // todo:??
    public function isEmpty(): bool
    {
        return $this->getParent()->isEmpty();
    }

    public function isReady(): bool
    {
        // todo: BUT WHY?
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getIterator(): \Generator
    {
        foreach ($this->commands as $command) {
            if (!$command->isReady() && $command instanceof \Traversable) {
                yield from $command;
                continue;
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
        $this->getParent()->onExecute($closure);
    }

    /**
     * To be called after parent transaction been commited.
     *
     * @param callable $closure
     */
    final public function onComplete(callable $closure)
    {
        $this->getParent()->onComplete($closure);
    }

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    final public function onRollBack(callable $closure)
    {
        $this->getParent()->onRollBack($closure);
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
     * @return ContextualCommandInterface
     */
    protected function getParent(): ContextualCommandInterface
    {
        if (empty($this->parent)) {
            throw new CommandException("Chain target command is not set.");
        }

        return $this->parent;
    }
}