<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Heap\Traits\ClaimTrait;
use Cycle\ORM\Heap\Traits\ContextTrait;
use Cycle\ORM\Heap\Traits\RelationTrait;
use Cycle\ORM\Heap\Traits\VisitorTrait;
use JetBrains\PhpStorm\ExpectedValues;
use SplObjectStorage;

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

    private array $data;

    private array $transactionData;

    /** @var ContextCarrierInterface[] */
    private array $consumers = [];

    /** @var SplObjectStorage[] */
    private array $storage = [];

    public function __construct(
        #[ExpectedValues(valuesFromClass: Node::class)]
        int $state,
        array $data
    ) {
        $this->state = $state;
        $this->data = $data;
        $this->transactionData = $data;
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
        if ($data === []) {
            return;
        }

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

    public function setTransactionData(array $data): array
    {
        return $this->transactionData = $data + $this->transactionData;
    }

    /**
     * Storage to store temporary cross entity links.
     *
     * @internal
     */
    public function getStorage(string $type): SplObjectStorage
    {
        if (!isset($this->storage[$type])) {
            $this->storage[$type] = new SplObjectStorage();
        }

        return $this->storage[$type];
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
            (string)($this->data[$key] ?? 'NULL'),
            [ConsumerInterface::DATA => 'DATA', ConsumerInterface::SCOPE => 'SCOPE'][$stream]
        );
        if ($trigger || !empty($this->data[$key])) {
            $this->register($key, $this->data[$key] ?? null, false, $stream);
        }
    }

    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = self::DATA
    ): void {
        $oldValue = $this->data[$key] ?? null;
        if (!$fresh && !is_object($oldValue)) {
            // custom, non value objects can be supported here
            $fresh = $oldValue != $value;
        }

        // if (!array_key_exists($key, $this->transactionData)) {
        //     $this->transactionData[$key] = $value;
        // }

        #
        \Cycle\ORM\Transaction\Pool::DEBUG and print sprintf(
            "State(%s):Register %s {$key} => %s\n",
            spl_object_id($this),
            $fresh ? 'fresh' : '',
            var_export($value, true)
        );
        // if ($key === 'user_id' && $value === '1') {
        //     throw new \Exception();
        // }
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
