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
 * Splits input context command into 2 destinations: original create command (usually insert) and delayed update command.
 * Used to properly unfold cyclic graphs by keeping the reference data in update and solves the issue of multiple
 * parent by sending the data to the first command.
 *
 * Handlers are attached to the head command since we can guarantee that head would always be executed.
 */
class Split implements ContextualInterface, \IteratorAggregate
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
     */
    public function setContext(string $key, $value)
    {
        $this->contextPath[$key]->setContext($key, $value);
    }

    public function accept($k, $v){
        $this->contextPath[$k]->accept($k, $v);
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
        $this->tail->onExecute($closure);
    }

    /**
     * @inheritdoc
     */
    public function onComplete(callable $closure)
    {
        $this->head->onComplete($closure);
        $this->tail->onComplete($closure);
    }

    /**
     * @inheritdoc
     */
    public function onRollBack(callable $closure)
    {
        $this->head->onRollBack($closure);
        $this->tail->onRollBack($closure);
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