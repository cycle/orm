<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\ContextualInterface;

/**
 * Doing noting.
 *
 * @codeCoverageIgnore
 */
final class Nil implements ContextualInterface
{
    /**
     * {@inheritdoc}
     */
    public function waitContext(string $key, bool $required = true)
    {

    }

    /**
     * {@inheritdoc}
     */
    public function freeContext(string $key)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function getContext(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function setContext(string $key, $value)
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

    /**
     * Handler to be invoked when command is being destructed.
     *
     * @param callable $closure
     */
    public function onDestruct(callable $closure)
    {
        // nothing to do
    }
}