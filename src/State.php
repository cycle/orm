<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Traits\ReferenceTrait;
use Spiral\ORM\Traits\RelationTrait;
use Spiral\ORM\Traits\VisitorTrait;

/**
 * State carries meta information about all load entities, including original set of data,
 * relations, state and number of active references (in cases when entity become unclaimed).
 */
final class State implements StateInterface
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
    private $handlers = [];

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
     * @inheritdoc
     */
    public function onChange(callable $handler)
    {
        $this->handlers[] = $handler;
    }

    /**
     * @inheritdoc
     */
    public function setState(int $state): void
    {
        $this->state = $state;
    }

    /**
     * @inheritdoc
     */
    public function getState(): int
    {
        return $this->state;
    }

    /**
     * @inheritdoc
     */
    public function setData(array $data)
    {
        if (empty($data)) {
            return;
        }

        $this->data = $data + $this->data;
        foreach ($this->handlers as $handler) {
            call_user_func($handler, $this);
        }
    }

    /**
     * @inheritdoc
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Reset state.
     */
    public function __destruct()
    {
        $this->data = [];
        $this->relations = [];
        $this->handlers = [];
        $this->visited = [];
    }
}