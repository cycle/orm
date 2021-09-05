<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Heap\Traits\WaitFieldTrait;
use Cycle\ORM\Heap\Traits\RelationTrait;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Current node state.
 */
final class State implements ConsumerInterface
{
    use RelationTrait;
    use WaitFieldTrait;

    /** @var array<string, mixed> */
    private array $data;

    private array $transactionData;

    /** @var array<string, Node[]> */
    private array $storage = [];

    public function __construct(
        #[ExpectedValues(valuesFromClass: Node::class)]
        private int $state,
        array $data
    ) {
        $this->data = $data;
        $this->transactionData = $state === Node::NEW ? [] : $data;
    }

    /**
     * Storage to store temporary cross entity nodes.
     *
     * @return Node[]
     *
     * @internal
     */
    public function getStorage(string $type): array
    {
        return $this->storage[$type] ?? ($this->storage[$type] = []);
    }

    public function addToStorage(string $type, Node $node): void
    {
        $this->storage[$type][] = $node;
    }

    public function clearStorage(string $type = null): void
    {
        if ($type === null) {
            $this->storage = [];
        } else {
            unset($this->storage[$type]);
        }
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

    public function updateTransactionData(array $fields = null): void
    {
        if ($fields === null) {
            $this->transactionData = array_merge($this->transactionData, $this->data);
            $this->state = Node::MANAGED;
            return;
        }
        $changes = false;
        foreach ($this->data as $field => $value) {
            if (in_array($field, $fields, true)) {
                $this->transactionData[$field] = $this->data[$field];
                continue;
            }
            $changes = $changes || Node::compare($value, $this->transactionData[$field] ?? null) !== 0;
        }
        if (!$changes) {
            $this->state = Node::MANAGED;
        }
    }

    public function getChanges(): array
    {
        if ($this->state === Node::NEW) {
            return $this->data;
        }

        return array_udiff_assoc($this->data, $this->transactionData, [Node::class, 'compare']);
    }

    /**
     * @return mixed|null
     */
    public function getValue(string $key): mixed
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
        mixed $value,
        int $stream = self::DATA
    ): void {
        $this->freeWaitingField($key);

        \Cycle\ORM\Transaction\Pool::DEBUG && print sprintf(
            "State(%s):Register {$key} => %s\n",
            spl_object_id($this),
            var_export($value, true)
        );

        $this->data[$key] = $value;
    }

    public function isReady(): bool
    {
        return $this->waitingFields === [];
    }

    public function __destruct()
    {
        unset($this->relations, $this->storage);
    }
}
