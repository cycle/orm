<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

class ConditionalCommand extends AbstractCommand implements \IteratorAggregate
{
    private $parent;
    private $condition;

    public function __construct(CommandInterface $parent, callable $condition)
    {
        $this->parent = $parent;
        $this->condition = $condition;
    }

    public function getIterator()
    {
        if (call_user_func($this->condition)) {
            yield $this->parent;
        }
    }

    public function execute()
    {
        // nothing
    }

    public function complete()
    {
        // nothing
    }

    public function rollBack()
    {
        // nothing
    }
}