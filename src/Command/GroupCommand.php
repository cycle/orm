<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

// todo: not chain? find better name for chain command?
class GroupCommand extends AbstractCommand implements \IteratorAggregate
{
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
}