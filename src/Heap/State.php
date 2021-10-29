<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Heap\Traits\WaitFieldTrait;
use Cycle\ORM\Heap\Traits\RelationTrait;
use DateTimeImmutable;
use DateTimeInterface;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Current node state.
 */
final class State implements ConsumerInterface
{
    use RelationTrait;
    use WaitFieldTrait;

    private array $transactionData;

    /** @var array<string, Node[]> */
    private array $storage = [];

    /**
     * @param array<string, mixed> $data
     * @param array<string, mixed> $transactionRaw
     */
    public function __construct(
        #[ExpectedValues(valuesFromClass: Node::class)]
        private int $state,
        private array $data,
        private array $transactionRaw
    ) {
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
            foreach ($this->data as $field => $value) {
                $this->transactionData[$field] = $value;
                if (isset($this->transactionRaw[$field])) {
                    $this->transactionRaw[$field] = Node::convertToSolid($this->data[$field]);
                }
            }
            $this->state = Node::MANAGED;
            return;
        }
        $changes = false;
        foreach ($this->data as $field => $value) {
            if (in_array($field, $fields, true)) {
                $this->transactionData[$field] = $this->data[$field];
                if (\array_key_exists($field, $this->transactionRaw)) {
                    $this->transactionRaw[$field] = Node::convertToSolid($this->data[$field]);
                }
                continue;
            }
            $changes = $changes || Node::compare($value, $this->transactionRaw[$field] ?? $this->transactionData[$field] ?? null) !== 0;
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
        $result = [];
        foreach ($this->data as $field => $value) {
            if (!\array_key_exists($field, $this->transactionData)) {
                $result[$field] = $value;
                continue;
            }
            $c = Node::compare(
                $value,
                \array_key_exists($field, $this->transactionRaw) ? $this->transactionRaw[$field] : $this->transactionData[$field]
            );
            if ($c !== 0) {
                $result[$field] = $value;
            }
        }
        return $result;
        // return array_udiff_assoc($this->data, $this->transactionData, [Node::class, 'compare']);
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
