<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Heap\Traits\ClaimTrait;
use Cycle\ORM\Heap\Traits\RelationTrait;
use Cycle\ORM\Heap\Traits\VisitorTrait;

/**
 * Current node state.
 */
final class State implements ConsumerInterface, ProducerInterface
{
    use RelationTrait;
    use ClaimTrait;
    use VisitorTrait;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /** @var null|ContextCarrierInterface */
    private $command;

    /** @var ContextCarrierInterface[] */
    private $consumers;

    /** @var \SplObjectStorage[] */
    private $storage = [];

    /**
     * @param int   $state
     * @param array $data
     */
    public function __construct(int $state, array $data)
    {
        $this->state = $state;
        $this->data = $data;
    }

    /**
     * Set new state value.
     *
     * @param int $state
     */
    public function setStatus(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Get current state.
     *
     * @return int
     */
    public function getStatus(): int
    {
        return $this->state;
    }

    /**
     * Set new state data (will trigger state handlers).
     *
     * @param array $data
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
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Set the reference to the object creation command (non executed).
     *
     * @param ContextCarrierInterface|null $cmd
     * @internal
     */
    public function setCommand(ContextCarrierInterface $cmd = null): void
    {
        $this->command = $cmd;
    }

    /**
     * @return null|ContextCarrierInterface
     * @internal
     */
    public function getCommand(): ?ContextCarrierInterface
    {
        return $this->command;
    }

    /**
     * Storage to store temporary cross entity links.
     *
     * @param string $type
     * @return \SplObjectStorage
     * @internal
     */
    public function getStorage(string $type): \SplObjectStorage
    {
        if (!isset($this->storage[$type])) {
            $this->storage[$type] = new \SplObjectStorage();
        }

        return $this->storage[$type];
    }

    /**
     * @inheritdoc
     */
    public function forward(
        string $key,
        ConsumerInterface $consumer,
        string $target,
        bool $trigger = false,
        int $stream = ConsumerInterface::DATA
    ): void {
        $this->consumers[$key][] = [$consumer, $target, $stream];

        if ($trigger || !empty($this->data[$key])) {
            $this->register($key, $this->data[$key] ?? null, false, $stream);
        }
    }

    /**
     * @inheritdoc
     */
    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = self::DATA
    ): void {
        if (!$fresh) {
            // custom, non value objects can be supported here
            $fresh = ($this->data[$key] ?? null) != $value;
        }

        $this->data[$key] = $value;

        // cascade
        if (!empty($this->consumers[$key])) {
            foreach ($this->consumers[$key] as $id => $consumer) {
                /** @var ConsumerInterface $acc */
                $acc = $consumer[0];
                $acc->register($consumer[1], $value, $fresh, $consumer[2]);
                $fresh = false;
            }
        }
    }
}
