<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\ContextualInterface;

/**
 * Branches input context into 2 destinations: original create command (insert) and delayed update command.
 * Used to properly unfold cyclic graphs but at the same time resolve issue with double linked data.
 *
 * Mapper must issue one Branch per object save to prevent extra update commands with highly coupled data.
 *
 * Branch will always execute head command but might delay or skip execution of the tail.
 */
class Branch implements ContextualInterface, \IteratorAggregate
{
    /** @var bool */
    private $headExecuted = false;

    /** @var ContextualInterface */
    private $head;

    /** @var ContextualInterface */
    private $tail;

    /** @var array */
    private $contextPath = [];

    /**
     * @param ContextualInterface $head
     * @param ContextualInterface $tail
     */
    public function __construct(ContextualInterface $head, ContextualInterface $tail)
    {
        $this->head = $head;
        $this->tail = $tail;

        $this->head->onExecute(function () {
            $this->headExecuted = true;
        });
    }

    /**
     * @inheritdoc
     */
    public function isReady(): bool
    {
        return $this->getTarget()->isReady();
    }

    /**
     * @return \Generator
     */
    public function getIterator(): \Generator
    {
        yield $this->getTarget();
    }

    /**
     * @inheritdoc
     */
    public function waitContext(string $key, bool $required = true)
    {
        if ($required) {
            $this->contextPath[$key] = $this->head;
        } else {
            $this->contextPath[$key] = $this->tail;
        }

        $this->contextPath[$key]->waitContext($key, true);
    }

    /**
     * @inheritdoc
     */
    public function freeContext(string $key)
    {
        $this->contextPath[$key]->freeContext($key, true);
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function getContext(): array
    {
        // branch can not hold the context, only underlying commands can
        return [];
    }

    /**
     * @inheritdoc
     */
    public function setContext(string $key, $value)
    {
        $this->contextPath[$key]->setContext($key, $value);
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function execute()
    {
        // delegated
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function complete()
    {
        // delegated
    }

    /**
     * @inheritdoc
     * @codeCoverageIgnore
     */
    public function rollBack()
    {
        // delegated
    }

    /**
     * @inheritdoc
     */
    public function onExecute(callable $closure)
    {
        $this->head->onExecute($closure);
    }

    /**
     * @inheritdoc
     */
    public function onComplete(callable $closure)
    {
        $this->head->onComplete($closure);
    }

    /**
     * @inheritdoc
     */
    public function onRollBack(callable $closure)
    {
        $this->head->onRollBack($closure);
    }

    /**
     * @inheritdoc
     */
    protected function getTarget(): ContextualInterface
    {
        if ($this->headExecuted) {
            return $this->tail;
        }

        return $this->head;
    }
}