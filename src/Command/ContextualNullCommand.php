<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

// todo: rename and make it better
class ContextualNullCommand implements ContextualInterface
{
    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(string $name, $value)
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function isReady(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function complete()
    {
        // nothing to do
    }

    /**
     * {@inheritdoc}
     */
    public function rollBack()
    {
        // nothing to do
    }

    /**
     * Closure to be called after command executing.
     *
     * @param callable $closure
     */
    public function onExecute(callable $closure)
    {
        // nothing to do
    }

    /**
     * To be called after parent transaction been commited.
     *
     * @param callable $closure
     */
    public function onComplete(callable $closure)
    {
        // nothing to do
    }

    /**
     * To be called after parent transaction been rolled back.
     *
     * @param callable $closure
     */
    public function onRollBack(callable $closure)
    {
        // nothing to do
    }
}