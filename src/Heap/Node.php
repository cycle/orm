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
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;

/**
 * Node (metadata) carries meta information about entity state, changes forwards data to other points through
 * inner states.
 */
final class Node implements ProducerInterface, ConsumerInterface
{
    use RelationTrait;

    // Different entity states in a pool
    public const PROMISED = 0;
    public const NEW = 1;
    public const MANAGED = 2;
    public const SCHEDULED_INSERT = 3;
    public const SCHEDULED_UPDATE = 4;
    public const SCHEDULED_DELETE = 5;
    public const DELETED = 6;

    /** @var string */
    private $role;

    /** @var int */
    private $status;

    /** @var array */
    private $data;

    /** @var State|null */
    private $state;

    private $dataObjectsState = [];

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

        $this->setObjectsState($data);
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

    public function hasState(): bool
    {
        return $this->state !== null;
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
     * Get current state data. Mutalbe inside the transaction.
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
     * The intial (post-load) node date. Does not change during the transaction.
     *
     * @return array
     */
    public function getInitialData(): array
    {
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

        $changes = $this->getChanges($this->state->getData(), $this->data);
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
     *
     * @return int
     */
    public static function compare($a, $b): int
    {
        if ($a === $b) {
            return 0;
        }
        if ($a === null xor $b === null) {
            return 1;
        }

        $ta = [\gettype($a), \gettype($b)];

        // array, boolean, double, integer, object, string
        \sort($ta, SORT_STRING);

        if ($ta[0] === 'object' || $ta[1] === 'object') {
            // Both are objects
            if ($ta[0] === $ta[1]) {
                if ($a instanceof DateTimeInterface && $b instanceof DateTimeInterface) {
                    return $a <=> $b;
                }
                if (self::isStringable($a) && self::isStringable($b)) {
                    return $a->__toString() <=> $b->__toString();
                }
                return (int)(\get_class($a) !== \get_class($b) || (array)$a !== (array)$b);
            }
            // Object and string/int
            if ($ta[1] === 'string' || $ta[0] === 'integer') {
                $a = self::isStringable($a) ? $a->__toString() : (!is_object($a) ? (string) $a : $a);
                $b = self::isStringable($b) ? $b->__toString() : (!is_object($b) ? (string) $b : $b);
                return $a <=> $b;
            }
            return -1;
        }

        if ($ta[1] === 'string') {
            if ($a === '' || $b === '') {
                return -1;
            }
            if ($ta[0] === 'integer') {
                return \is_numeric($a) && \is_numeric($b) ? (int)((string)$a !== (string)$b) : -1;
            }
            if ($ta[0] === 'double') {
                return \is_numeric($a) && \is_numeric($b) ? (int)((float)$a !== (float)$b) : -1;
            }
        }

        if ($ta[0] === 'boolean') {
            $a = \filter_var($a, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $b = \filter_var($b, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return (int)($a !== $b);
        }

        if ($ta === ['double', 'integer']) {
            return (int)((float)$a !== (float)$b);
        }

        return 1;
    }

    public function getChanges(array $current, array $from): array
    {
        foreach ($this->dataObjectsState as $field => $value) {
            if (\is_string($value) && self::isStringable($current[$field])) {
                if ((string) $current[$field] !== $value) {
                    unset($from[$field]);
                }
                continue;
            }
            if ($value instanceof DateTimeImmutable && ($value <=> $current[$field]) !== 0) {
                unset($from[$field]);
            }
        }

        // in a future mapper must support solid states
        return \array_udiff_assoc($current, $from, [self::class, 'compare']);
    }

    protected function setObjectsState(array $data): void
    {
        foreach ($data as $field => $value) {
            if (static::isStringable($value)) {
                $this->dataObjectsState[$field] = (string) $value;
                continue;
            }
            if ($value instanceof DateTime) {
                $this->dataObjectsState[$field] = DateTimeImmutable::createFromMutable($value);
            }
        }
    }

    protected static function isStringable($value): bool
    {
        return \is_object($value) && \method_exists($value, '__toString');
    }
}
