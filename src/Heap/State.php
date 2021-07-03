<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Heap\Traits\ClaimTrait;
use Cycle\ORM\Heap\Traits\ContextTrait;
use Cycle\ORM\Heap\Traits\RelationTrait;
use Cycle\ORM\Heap\Traits\VisitorTrait;
use Cycle\ORM\Relation\Pivoted\PivotedStorage;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Current node state.
 */
final class State implements ConsumerInterface, ProducerInterface
{
    use RelationTrait;
    use ClaimTrait;
    use VisitorTrait;
    use ContextTrait;

    private int $state;

    /** @var array<string, mixed> */
    private array $data;

    /** @var array<string, mixed> */

    private array $transactionData;

    /** @var ConsumerInterface[] */
    private array $consumers = [];

    /** @var PivotedStorage[] */
    private array $storage = [];

    public function __construct(
        #[ExpectedValues(valuesFromClass: Node::class)]
        int $state,
        array $data
    ) {
        $this->state = $state;
        $this->data = $data;
        $this->transactionData = $state === Node::NEW ? [] : $data;
    }

    /**
     * Set new state value.
     */
    public function setStatus(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Get current state.
     */
    public function getStatus(): int
    {
        return $this->state;
    }

    /**
     * Set new state data (will trigger state handlers).
     */
    public function setData(array $data): void
    {
        foreach ($data as $column => $value) {
            $this->register($column, $value);
        }
    }

    /**
     * Get current state data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get current state data.
     */
    public function getTransactionData(): array
    {
        return $this->transactionData;
    }

    public function updateTransactionData(): void
    {
        $this->transactionData = array_merge($this->transactionData, $this->data);
        $this->state = Node::MANAGED;
    }

    public function getChanges(): array
    {
        if ($this->state === Node::NEW) {
            return $this->data;
        }

        return array_udiff_assoc($this->data, $this->transactionData, [Node::class, 'compare']);
    }

    /**
     * Storage to store temporary cross entity links.
     *
     * @internal
     */
    public function getStorage(string $type): ?PivotedStorage
    {
        return $this->storage[$type] ?? null;
    }

    public function setStorage(string $type, PivotedStorage $storage): void
    {
        $this->storage[$type] = $storage;
    }

    public function forward(
        string $key,
        ConsumerInterface $consumer,
        string $target,
        bool $trigger = false,
        int $stream = ConsumerInterface::DATA
    ): void {
        $this->consumers[$key][] = [$consumer, $target, $stream];

        \Cycle\ORM\Transaction\Pool::DEBUG AND print sprintf(
            "Forward to State! [%s]  target: $target, key: $key, value: %s Stream: %s\n",
            $consumer instanceof Node ? 'Node ' . $consumer->getRole() : get_class($consumer),
            (string)($this->getValue($key) ?? 'NULL'),
            [ConsumerInterface::DATA => 'DATA', ConsumerInterface::SCOPE => 'SCOPE'][$stream]
        );
        if ($trigger || !empty($this->getValue($key))) {
            $this->register($key, $this->getValue($key), false, $stream);
        }
    }

    /**
     * @return null|mixed
     */
    public function getValue(string $key)
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : ($this->transactionData[$key] ?? null);
    }

    public function hasValue(string $key, bool $allowNull = true): bool
    {
        if (!$allowNull) {
            return isset($this->data[$key]) || isset($this->transactionData[$key]);
        }
        return array_key_exists($key, $this->data) || array_key_exists($key, $this->transactionData);
    }

    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = self::DATA
    ): void {
        $oldValue = $this->getValue($key);

        if (!$fresh && !is_object($oldValue)) {
            // custom, non value objects can be supported here
            $fresh = $oldValue != $value;
        }
        if ($fresh || $value !== null) {
            $this->freeContext($key);
        }

        \Cycle\ORM\Transaction\Pool::DEBUG and print sprintf(
            "State(%s):Register %s {$key} => %s\n",
            spl_object_id($this),
            $fresh ? 'fresh' : '',
            var_export($value, true)
        );

        $this->data[$key] = $value;

        // cascade
        if (!empty($this->consumers[$key])) {
            foreach ($this->consumers[$key] as $consumer) {
                /** @var ConsumerInterface $acc */
                $acc = $consumer[0];
                $acc->register($consumer[1], $value, $fresh, $consumer[2]);
                $fresh = false;
            }
        }
    }

    public function isReady(): bool
    {
        return $this->waitContext === [];
    }
}
