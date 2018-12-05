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
 * State carries meta information about all load entities, including original set of data,
 * relations, state and number of active references (in cases when entity become unclaimed).
 */
final class State implements ForwarderInterface, AcceptorInterface
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

    /** @var null|CarrierInterface */
    private $leadCommand;

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
     * @param CarrierInterface|null $cmd
     */
    public function setLeadCommand(CarrierInterface $cmd = null)
    {
        $this->leadCommand = $cmd;
    }

    /**
     * @internal
     * @return null|CarrierInterface
     */
    public function getLeadCommand(): ?CarrierInterface
    {
        return $this->leadCommand;
    }

    private $routing;

    /**
     * @inheritdoc
     */
    public function forward(
        string $key,
        AcceptorInterface $acceptor,
        string $target,
        bool $trigger = false,
        int $type = AcceptorInterface::DATA
    ) {
        $this->routing[$key][] = [$acceptor, $target, $type];

        if ($trigger && !empty($this->data[$key])) {
            $this->accept($target, $this->data[$key], false, $type);
        }
    }

    /**
     * @inheritdoc
     */
    public function accept(
        string $key,
        ?string $value,
        bool $handled = false,
        int $type = self::DATA
    ) {
        $handled = $handled || (($this->data[$key] ?? null) == $value);
        $this->data[$key] = $value;

        // cascade
        if (!empty($this->routing[$key])) {
            foreach ($this->routing[$key] as $id => $handler) {
                call_user_func([$handler[0], 'accept'], $handler[1], $value, $handled, $handler[2]);
                $handled = true;
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
        $this->leadCommand = null;
    }
}