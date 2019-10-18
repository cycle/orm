<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap;

use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Context\ProducerInterface;
use Cycle\ORM\Heap\Traits\RelationTrait;

/**
 * Node (metadata) carries meta information about entity state, changes forwards data to other points through
 * inner states.
 */
final class Node implements ProducerInterface, ConsumerInterface
{
    use RelationTrait;

    // Different entity states in a pool
    public const PROMISED         = 0;
    public const NEW              = 1;
    public const MANAGED          = 2;
    public const SCHEDULED_INSERT = 3;
    public const SCHEDULED_UPDATE = 4;
    public const SCHEDULED_DELETE = 5;
    public const DELETED          = 6;

    /** @var int */
    private $status;

    /** @var array */
    private $data;

    /** @var string */
    private $role;

    /** @var null|State */
    private $state;

    /**
     * @param int    $status
     * @param array  $data
     * @param string $role
     */
    public function __construct(int $status, array $data, string $role)
    {
        $this->status = $status;
        $this->data = $data;
        $this->role = $role;
    }

    /**
     * Reset state.
     */
    public function __destruct()
    {
        $this->data = [];
        $this->state = null;
        $this->relations = [];
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * Current point state (set of changes).
     *
     * @return State
     */
    public function getState(): State
    {
        if ($this->state === null) {
            $this->state = new State($this->status, $this->data);
        }

        return $this->state;
    }

    /**
     * Set new state value.
     *
     * @param int $state
     */
    public function setStatus(int $state): void
    {
        $this->getState()->setStatus($state);
    }

    /**
     * Get current state.
     *
     * @return int
     */
    public function getStatus(): int
    {
        if ($this->state !== null) {
            return $this->state->getStatus();
        }

        return $this->status;
    }

    /**
     * Set new state data (will trigger state handlers).
     *
     * @param array $data
     */
    public function setData(array $data): void
    {
        $this->getState()->setData($data);
    }

    /**
     * Get current state data.
     *
     * @return array
     */
    public function getData(): array
    {
        if ($this->state !== null) {
            return $this->state->getData();
        }

        return $this->data;
    }

    /**
     * @inheritdoc
     */
    public function forward(
        string $key,
        ConsumerInterface $consumer,
        string $target,
        bool $trigger = false,
        int $stream = self::DATA
    ): void {
        $this->getState()->forward($key, $consumer, $target, $trigger, $stream);
    }

    /**
     * @inheritdoc
     */
    public function register(string $key, $value, bool $fresh = false, int $stream = self::DATA): void
    {
        $this->getState()->register($key, $value, $fresh, $stream);
    }

    /**
     * Sync the point state and return data diff.
     *
     * @return array
     */
    public function syncState(): array
    {
        if ($this->state === null) {
            return [];
        }

        $changes = array_udiff_assoc($this->state->getData(), $this->data, [static::class, 'compare']);
        foreach ($this->state->getRelations() as $name => $relation) {
            $this->setRelation($name, $relation);
        }

        // DELETE handled separately
        $this->status = self::MANAGED;
        $this->data = $this->state->getData();
        $this->state = null;

        return $changes;
    }

    /**
     * Reset point state and flush all the changes.
     */
    public function resetState(): void
    {
        $this->state = null;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    private static function compare($a, $b): int
    {
        if ($a == $b) {
            return 0;
        }

        return ($a > $b) ? 1 : -1;
    }
}
