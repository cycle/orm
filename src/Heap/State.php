<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Heap;

use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Context\ProducerInterface;
use Spiral\Cycle\Heap\Traits\ClaimTrait;
use Spiral\Cycle\Heap\Traits\RelationTrait;
use Spiral\Cycle\Heap\Traits\VisitorTrait;

/**
 * Current node state.
 */
class State implements ConsumerInterface, ProducerInterface
{
    use RelationTrait, ClaimTrait, VisitorTrait;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /** @var null|ContextCarrierInterface */
    private $command;

    /** @var ContextCarrierInterface[] */
    private $consumers;

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
    public function setData(array $data)
    {
        if (empty($data)) {
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
     * @internal
     * @param ContextCarrierInterface|null $cmd
     */
    public function setCommand(ContextCarrierInterface $cmd = null)
    {
        $this->command = $cmd;
    }

    /**
     * @internal
     * @return null|ContextCarrierInterface
     */
    public function getCommand(): ?ContextCarrierInterface
    {
        return $this->command;
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
    ) {
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
        bool $update = false,
        int $stream = self::DATA
    ) {
        if (!$update) {
            $update = ($this->data[$key] ?? null) != $value;
        }

        $this->data[$key] = $value;

        // cascade
        if (!empty($this->consumers[$key])) {
            foreach ($this->consumers[$key] as $id => $consumer) {
                /** @var ConsumerInterface $acc */
                $acc = $consumer[0];
                $acc->register($consumer[1], $value, $update, $consumer[2]);
                $update = false;
            }
        }
    }
}