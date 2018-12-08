<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Context\AcceptorInterface;
use Spiral\ORM\Context\ForwarderInterface;
use Spiral\ORM\Traits\ReferenceTrait;
use Spiral\ORM\Traits\RelationTrait;
use Spiral\ORM\Traits\VisitorTrait;

/**
 * Point state.
 */
class State implements AcceptorInterface, ForwarderInterface
{
    use RelationTrait, ReferenceTrait, VisitorTrait;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /** @var null|CarrierInterface */
    private $command;

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
            $this->push($column, $value);
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
     * @param CarrierInterface|null $cmd
     */
    public function setCommand(CarrierInterface $cmd = null)
    {
        $this->command = $cmd;
    }

    /**
     * @internal
     * @return null|CarrierInterface
     */
    public function getCommand(): ?CarrierInterface
    {
        return $this->command;
    }

    private $handlers;

    /**
     * @inheritdoc
     */
    public function pull(
        string $key,
        AcceptorInterface $acceptor,
        string $target,
        bool $trigger = false,
        int $stream = AcceptorInterface::DATA
    ) {
        $this->handlers[$key][] = [$acceptor, $target, $stream];

        if ($trigger || !empty($this->data[$key])) {
            $this->push($key, $this->data[$key] ?? null, false, $stream);
        }
    }

    /**
     * @inheritdoc
     */
    public function push(string $key, $value, bool $update = false, int $stream = self::DATA)
    {
        if (!$update) {
            $update = ($this->data[$key] ?? null) != $value;
        }

        $this->data[$key] = $value;

        // cascade
        if (!empty($this->handlers[$key])) {
            foreach ($this->handlers[$key] as $id => $h) {
                /** @var AcceptorInterface $acc */
                $acc = $h[0];
                $acc->push($h[1], $value, $update, $h[2]);
                $update = false;
            }
        }
    }
}