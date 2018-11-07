<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Command;

/**
 * Provides support for command events.
 */
abstract class AbstractCommand implements CommandInterface
{
    /** @var callable[] */
    private $onExecute = [];

    /** @var callable[] */
    private $onComplete = [];

    /** @var callable[] */
    private $onRollBack = [];

    /**
     * Closure to be called after command executing.
     *
     * @param callable $closure
     */
    final public function onExecute(callable $closure)
    {
        $this->onExecute[] = $closure;
    }

    /**
     * To be called after parent transaction been commited.
     *
     * @param callable $closure
     */
    final public function onComplete(callable $closure)
    {
        $this->onComplete[] = $closure;
    }

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    final public function onRollBack(callable $closure)
    {
        $this->onRollBack[] = $closure;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        foreach ($this->onExecute as $closure) {
            call_user_func($closure, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        foreach ($this->onComplete as $closure) {
            call_user_func($closure, $this);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        foreach ($this->onRollBack as $closure) {
            call_user_func($closure, $this);
        }
    }
}