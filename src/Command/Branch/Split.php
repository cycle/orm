<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Branch;

use Cycle\ORM\Command\ContextCarrierInterface;
use Generator;
use IteratorAggregate;

/**
 * Splits input context command into 2 destinations:
 * original create command (usually insert) and delayed update command.
 * Used to properly unfold cyclic graphs by keeping the reference data in update and solves the issue of multiple
 * parent by sending the data to the first command.
 *
 * Handlers are attached to the head command since we can guarantee that head would always be executed.
 */
final class Split implements ContextCarrierInterface, IteratorAggregate
{
    private ContextCarrierInterface $head;

    private ContextCarrierInterface $tail;

    /** @var ContextCarrierInterface[] */
    private array $contextPath = [];

    public function __construct(ContextCarrierInterface $head, ContextCarrierInterface $tail)
    {
        $this->head = $head;
        $this->tail = $tail;
    }

    public function isExecuted(): bool
    {
        return $this->getTarget()->isExecuted();
    }

    public function isReady(): bool
    {
        return $this->getTarget()->isReady();
    }

    public function getIterator(): Generator
    {
        yield $this->getTarget();
    }

    public function waitContext(string $key, bool $required = true): void
    {
        if ($required) {
            $this->contextPath[$key] = $this->head;
        } else {
            $this->contextPath[$key] = $this->tail;
        }

        $this->contextPath[$key]->waitContext($key, true);
    }

    /**
     * @codeCoverageIgnore
     */
    public function getContext(): array
    {
        // branch can not hold the context, only underlying commands can
        return [];
    }

    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = self::DATA
    ): void {
        if (isset($this->contextPath[$key])) {
            $this->contextPath[$key]->register($key, $value, $fresh, $stream);
        }
    }

    /**
     * @codeCoverageIgnore
     */
    public function execute(): void
    {
        // delegated
    }

    /**
     * @codeCoverageIgnore
     */
    public function complete(): void
    {
        // delegated
    }

    /**
     * @codeCoverageIgnore
     */
    public function rollBack(): void
    {
        // delegated
    }

    private function getTarget(): ContextCarrierInterface
    {
        if (!$this->head->isExecuted()) {
            return $this->head;
        }

        return $this->tail;
    }
}
