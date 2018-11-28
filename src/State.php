<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ContextualInterface;
use Spiral\ORM\Traits\ReferenceTrait;
use Spiral\ORM\Traits\RelationTrait;
use Spiral\ORM\Traits\VisitorTrait;

/**
 * State carries meta information about all load entities, including original set of data,
 * relations, state and number of active references (in cases when entity become unclaimed).
 */
final class State
{
    use RelationTrait, ReferenceTrait, VisitorTrait;

    // Different entity states in a pool
    public const PROMISED         = 0;
    public const NEW              = 1;
    public const LOADED           = 2;
    public const SCHEDULED_INSERT = 3;
    public const SCHEDULED_UPDATE = 4;
    public const SCHEDULED_DELETE = 5;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /**
     * @invisible
     * @var array
     */
    private $listeners = [];

    /** @var null|ContextualInterface */
    private $activeCommand;

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
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * Get current state.
     *
     * @return int
     */
    public function getState(): int
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

        $this->data = $data + $this->data;
        foreach ($this->listeners as $handler) {
            call_user_func($handler, $this);
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
     * Carries reference to the last issued command.
     *
     * @internal
     * @param ContextualInterface|null $cmd
     */
    public function setActiveCommand(ContextualInterface $cmd = null)
    {
        $this->activeCommand = $cmd;
    }

    /**
     * @internal
     * @return null|ContextualInterface
     */
    public function getActiveCommand(): ?ContextualInterface
    {
        return $this->activeCommand;
    }

    /**
     * Handle changes in state data.
     *
     * @internal
     * @param callable $listener
     */
    public function attachListener(callable $listener)
    {
        $this->listeners[] = $listener;
    }

    /**
     * Remove state change handler.
     *
     * @param callable $listener
     */
    public function removeListener(callable $listener)
    {
        foreach ($this->listeners as $index => $handler) {
            if ($handler === $listener) {
                unset($this->listeners[$index]);
                break;
            }
        }
    }

    /**
     * Reset state.
     */
    public function __destruct()
    {
        $this->data = [];
        $this->relations = [];
        $this->listeners = [];
        $this->visited = [];
    }
}