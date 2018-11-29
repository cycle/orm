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

    /** @var string */
    private $alias;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /** @var null|ContextualInterface */
    private $leadCommand;

    /**
     * Listeners used to provide context and scope clarification in non complete
     * dependency graph. Must only be used within Transaction scope.
     *
     * @invisible
     * @var array
     */
    private $listeners = [];

    /**
     * @param int    $state
     * @param array  $data
     * @param string $alias
     */
    public function __construct(int $state, array $data, string $alias)
    {
        $this->state = $state;
        $this->data = $data;
        $this->alias = $alias;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
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
        foreach ($this->listeners as $id => $handler) {
            if (call_user_func($handler, $this) === true) {
                unset($this->listeners[$id]);
            }
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
     * @param ContextualInterface|null $cmd
     */
    public function setLeadCommand(ContextualInterface $cmd = null)
    {
        $this->leadCommand = $cmd;
    }

    /**
     * @internal
     * @return null|ContextualInterface
     */
    public function getLeadCommand(): ?ContextualInterface
    {
        return $this->leadCommand;
    }

    /**
     * Handle changes in state data. Listener should return true to automatically detach itself.
     *
     * @internal
     * @param callable $closure
     */
    public function addListener(callable $closure)
    {
        $this->listeners[] = $closure;
    }

    /**
     * Remove all state listeners.
     */
    public function resetListeners()
    {
        $this->listeners = [];
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
        $this->leadCommand = null;
    }
}