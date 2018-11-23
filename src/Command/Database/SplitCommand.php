<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Database;

use Spiral\ORM\Command\ContextualInterface;

/**
 * Split input context into 2 destinations: original create command (insert) and delayed update command.
 * Used to properly unfold cyclic graphs but at the same time resolve issue with double linked data.
 *
 * Mapper must issue one SplitCommand per object save to prevent extra update commands with highly coupled data.
 */
class SplitCommand implements ContextualInterface, \IteratorAggregate
{
    private $head;
    private $tail;

    private $routes = [];
    private $headExecuted = false;

    public function __construct(ContextualInterface $head, ContextualInterface $tail)
    {
        $this->head = $head;
        $this->tail = $tail;

        $this->head->onExecute(function () {
            $this->headExecuted = true;
        });
    }

    public function __toString()
    {
        return get_class($this->getTarget());
    }

    public function getIterator()
    {
        yield $this->getTarget();
    }

    public function isReady(): bool
    {
        return $this->getTarget()->isReady();
    }

    public function execute()
    {
        // delegated
    }

    public function complete()
    {
        // delegated
    }

    public function rollBack()
    {
        // delegated
    }

    public function onExecute(callable $closure)
    {
        $this->getTarget()->onExecute($closure);
    }

    public function onComplete(callable $closure)
    {
        $this->getTarget()->onComplete($closure);
    }

    public function onRollBack(callable $closure)
    {
        $this->getTarget()->onRollBack($closure);
    }

    public function waitContext(string $key, bool $required = true)
    {
        if ($required) {
            $this->routes[$key] = $this->head;
        } else {
            $this->routes[$key] = $this->tail;
        }

        $this->routes[$key]->waitContext($key, true);
    }

    public function freeContext(string $key)
    {
        $this->routes[$key]->freeContext($key, true);
    }

    public function getContext(): array
    {
        return $this->tail->getContext() + $this->head->getContext();
    }

    public function setContext(string $key, $value)
    {
        $this->routes[$key]->setContext($key, $value);
    }

    protected function getTarget(): ContextualInterface
    {
        if ($this->headExecuted) {
            return $this->tail;
        }

        return $this->head;
    }
}