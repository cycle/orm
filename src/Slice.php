<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\ContextualInterface;

class Slice
{
    /** @var string */
    private $alias;

    /** @var int */
    private $state;

    /** @var array */
    private $data;

    /** @var null|ContextualInterface */
    private $command;

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

        foreach ($data as $column => $value) {
            $this->accept($column, $value);
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
    public function setCommand(ContextualInterface $cmd = null)
    {
        $this->command = $cmd;
    }

    /**
     * @internal
     * @return null|ContextualInterface
     */
    public function getCommand(): ?ContextualInterface
    {
        return $this->command;
    }

    private $routing;

    public function forward($target, $source, $into, bool $trigger = false)
    {
        $this->routing[$source][] = [$target, $into];

        if ($trigger && !empty($this->data[$source])) {
            $this->accept($source, $this->data[$source], true);
        }
    }

    public function accept($column, $value, $changed = false)
    {
        $changed = $changed || !(($this->data[$column] ?? null) == $value);

        $this->data[$column] = $value;

        if (!empty($this->routing[$column])) {
            foreach ($this->routing[$column] as $id => $handler) {
                call_user_func([$handler[0], 'accept'], $handler[1], $value, $changed);
                $changed = false;
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
        $this->visited = [];
        $this->command = null;
    }
}