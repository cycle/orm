<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Traits\DestructTrait;

/**
 * Expose underlying command when condition is met. If condition not met - underlying command is
 * skipped.
 */
class Condition implements CommandInterface, \IteratorAggregate
{
    use DestructTrait;

    /** @var CommandInterface */
    private $parent;

    /** @var callable */
    private $condition;

    /**
     * @param CommandInterface $parent
     * @param callable         $condition
     */
    public function __construct(CommandInterface $parent, callable $condition)
    {
        $this->parent = $parent;
        $this->condition = $condition;
    }

    /**
     * @return \Generator
     */
    public function getIterator()
    {
        if (call_user_func($this->condition)) {
            yield $this->parent;
        }
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function isReady(): bool
    {
        // condition is always ready
        return true;
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function execute()
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function complete()
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function rollBack()
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function onExecute(callable $closure)
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function onComplete(callable $closure)
    {
        // nothing to do
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function onRollBack(callable $closure)
    {
        // nothing to do
    }
}