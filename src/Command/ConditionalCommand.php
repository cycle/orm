<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

// THIS IS AWEFUCKINGSOME THOUGH YIELD
class ConditionalCommand implements CommandPromiseInterface
{
    public function execute()
    {
        // TODO: Implement execute() method.
    }

    public function complete()
    {
        // TODO: Implement complete() method.
    }

    public function rollBack()
    {
        // TODO: Implement rollBack() method.
    }

    public function onExecute(callable $closure)
    {
        // TODO: Implement onExecute() method.
    }

    public function onComplete(callable $closure)
    {
        // TODO: Implement onComplete() method.
    }

    public function onRollBack(callable $closure)
    {
        // TODO: Implement onRollBack() method.
    }

    public function getPrimaryKey()
    {
        // TODO: Implement getPrimaryKey() method.
    }

    public function isEmpty(): bool
    {
        // TODO: Implement isEmpty() method.
    }

    public function getContext(): array
    {
        // TODO: Implement getContext() method.
    }

    public function addContext(string $name, $value)
    {
        // TODO: Implement addContext() method.
    }
}