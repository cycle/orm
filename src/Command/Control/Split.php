<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command\Control;

use Spiral\ORM\Command\CarrierInterface;

/**
 * Splits input context command into 2 destinations: original create command (usually insert) and delayed update command.
 * Used to properly unfold cyclic graphs by keeping the reference data in update and solves the issue of multiple
 * parent by sending the data to the first command.
 *
 * Handlers are attached to the head command since we can guarantee that head would always be executed.
 */
class Split implements CarrierInterface, \IteratorAggregate
{
    /** @var bool */
    private $headExecuted = false;

    /** @var CarrierInterface */
    private $head;

    /** @var CarrierInterface */
    private $tail;

    /** @var array */
    private $contextPath = [];

    /**
     * @param CarrierInterface $head
     * @param CarrierInterface $tail
     */
    public function __construct(CarrierInterface $head, CarrierInterface $tail)
    {
        $this->head = $head;
        $this->tail = $tail;

        // todo: optimize, can i wrap?
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
    public function setContext(string $key, $value)
    {
        $this->contextPath[$key]->setContext($key, $value);
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
    public function push(string $key, $value, bool $update = false, int $stream = self::DATA)
    {
        if (isset($this->contextPath[$key])) {
            $this->contextPath[$key]->push($key, $value, $update, $stream);
        }
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
    protected function getTarget(): CarrierInterface
    {
        if ($this->headExecuted) {
            return $this->tail;
        }

        return $this->head;
    }
}