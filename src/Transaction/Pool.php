<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Exception\PoolException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use JetBrains\PhpStorm\ExpectedValues;
use SplObjectStorage;
use Traversable;

/**
 * @internal
 *
 * @psalm-suppress TypeDoesNotContainType
 * @psalm-suppress RedundantCondition
 */
final class Pool implements \Countable
{
    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $storage;

    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $all;

    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $priorityStorage;

    /**
     * @var Tuple[]
     *
     * @psalm-var list<Tuple>
     */
    private array $unprocessed;

    private bool $priorityEnabled = false;
    private bool $priorityAutoAttach = false;
    private int $happens = 0;

    /**
     * Indicates that Pool is now iterating
     */
    private bool $iterating = false;

    public function __construct(
        private ORMInterface $orm
    ) {
        $this->storage = new SplObjectStorage();
        $this->all = new SplObjectStorage();
    }

    public function someHappens(): void
    {
        ++$this->happens;
    }

    public function attach(
        object $entity,
        #[ExpectedValues(values: [Tuple::TASK_STORE, Tuple::TASK_DELETE, Tuple::TASK_FORCE_DELETE])]
        int $task,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null,
        ?int $status = null,
        bool $highPriority = false,
        bool $persist = false
    ): Tuple {
        // Find existing
        $tuple = $this->offsetGet($entity);
        if ($tuple !== null) {
            $this->updateTuple($tuple, $task, $status, $cascade, $node, $state);
            if ($persist) {
                $this->snap($tuple, true);
            }
            return $tuple;
        }

        $tuple = new Tuple($task, $entity, $cascade, $status ?? Tuple::STATUS_PREPARING);
        if ($node !== null) {
            $tuple->node = $node;
        }
        if ($state !== null) {
            $tuple->state = $state;
        }

        return $this->smartAttachTuple($tuple, $highPriority, $persist);
    }

    private function smartAttachTuple(Tuple $tuple, bool $highPriority = false, bool $snap = false): Tuple
    {
        if ($tuple->status === Tuple::STATUS_PROCESSED) {
            $this->all->attach($tuple->entity, $tuple);
            return $tuple;
        }
        if ($tuple->status === Tuple::STATUS_PREPARING && $this->all->contains($tuple->entity)) {
            return $this->all->offsetGet($tuple->entity);
        }
        $this->all->attach($tuple->entity, $tuple);

        if ($this->iterating || $snap) {
            $this->snap($tuple);
        }

        if (isset($tuple->node) && $tuple->task === Tuple::TASK_DELETE) {
            $tuple->state->setStatus(Node::SCHEDULED_DELETE);
        }
        if (($this->priorityAutoAttach || $highPriority) && $tuple->status === Tuple::STATUS_PREPARING) {
            $this->priorityStorage->attach($tuple->entity, $tuple);
        } else {
            $this->storage->attach($tuple->entity, $tuple);
        }
        return $tuple;
    }

    public function attachStore(
        object $entity,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null,
        bool $highPriority = false,
        bool $persist = false
    ): Tuple {
        return $this->attach($entity, Tuple::TASK_STORE, $cascade, $node, $state, null, $highPriority, $persist);
    }

    public function attachDelete(
        object $entity,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null
    ): Tuple {
        return $this->attach($entity, Tuple::TASK_DELETE, $cascade, $node, $state);
    }

    public function offsetGet(object $entity): ?Tuple
    {
        return $this->all->contains($entity) ? $this->all->offsetGet($entity) : null;
    }

    /**
     * Smart iterator
     *
     * @return Traversable<object, Tuple>
     */
    public function openIterator(): Traversable
    {
        if ($this->iterating) {
            throw new \RuntimeException('Iterator is already open.');
        }
        $this->iterating = true;
        $this->activatePriorityStorage();
        $this->unprocessed = [];

        // Snap all entities before store
        while ($this->storage->valid()) {
            /** @var Tuple $tuple */
            $tuple = $this->storage->getInfo();
            $this->snap($tuple);
            if (!isset($tuple->node)) {
                $this->storage->detach($this->storage->current());
            } else {
                $this->storage->next();
            }
        }

        $stage = 0;
        do {
            // High priority first
            if ($this->priorityStorage->count() > 0) {
                $priorityStorage = $this->priorityStorage;
                foreach ($priorityStorage as $entity) {
                    $tuple = $priorityStorage->offsetGet($entity);
                    yield $entity => $tuple;
                    $this->trashIt($entity, $tuple, $priorityStorage);
                }
                continue;
            }
            // Other
            if ($this->storage->count() === 0) {
                break;
            }
            $pool = $this->storage;
            if (!$pool->valid() && $pool->count() > 0) {
                $pool->rewind();
            }
            if ($stage === 0) {
                // foreach ($pool as $entity) {
                while ($pool->valid()) {
                    /** @var Tuple $tuple */
                    $entity = $pool->current();
                    $tuple = $pool->getInfo();
                    $pool->next();
                    if ($tuple->status !== Tuple::STATUS_PREPARING) {
                        continue;
                    }
                    yield $entity => $tuple;
                    $this->trashIt($entity, $tuple, $this->storage);
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
                $this->priorityAutoAttach = true;
                $stage = 1;
                $this->storage->rewind();
            }
            if ($stage === 1) {
                while ($pool->valid()) {
                    /** @var Tuple $tuple */
                    $entity = $pool->current();
                    $tuple = $pool->getInfo();
                    $pool->next();
                    if ($tuple->status !== Tuple::STATUS_WAITING || $tuple->task === Tuple::TASK_DELETE) {
                        continue;
                    }
                    $tuple->status = Tuple::STATUS_WAITED;
                    yield $entity => $tuple;
                    $this->trashIt($entity, $tuple, $this->storage);
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
                $stage = 2;
                $this->storage->rewind();
            }
            if ($stage === 2) {
                $this->happens = 0;
                while ($pool->valid()) {
                    /** @var Tuple $tuple */
                    $entity = $pool->current();
                    $tuple = $pool->getInfo();
                    if ($tuple->task === Tuple::TASK_DELETE) {
                        $tuple->task = Tuple::TASK_FORCE_DELETE;
                    }
                    if ($tuple->status === Tuple::STATUS_WAITING) {
                        $tuple->status = Tuple::STATUS_WAITED;
                    } elseif ($tuple->status === Tuple::STATUS_DEFERRED) {
                        $tuple->status = Tuple::STATUS_PROPOSED;
                    }
                    $pool->next();
                    yield $entity => $tuple;
                    $this->trashIt($entity, $tuple, $this->storage);
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
                $hasUnresolved = $this->unprocessed !== [];
                if ($this->happens !== 0 && $hasUnresolved) {
                    /** @psalm-suppress InvalidIterator */
                    foreach ($this->unprocessed as $item) {
                        $this->storage->attach($item->entity, $item);
                    }
                    $this->unprocessed = [];
                    continue;
                }
                if ($this->happens === 0 && (\count($pool) > 0 || $hasUnresolved)) {
                    throw new PoolException('Pool has gone into an infinite loop.');
                }
            }
        } while (true);
        $this->closeIterator();
    }

    public function count(): int
    {
        return \count($this->storage) + ($this->priorityEnabled ? $this->priorityStorage->count() : 0);
    }

    /**
     * Get unresolved entity tuples.
     *
     * @return iterable<Tuple>
     */
    public function getUnresolved(): iterable
    {
        if ($this->iterating) {
            return $this->unprocessed;
        }
        throw new PoolException('The Pool iterator isn\'t open.');
    }

    /**
     * @return iterable<object, Tuple> All valid tuples
     */
    public function getAllTuples(): iterable
    {
        foreach ($this->all as $entity) {
            $tuple = $this->all->offsetGet($entity);
            if (isset($tuple->node)) {
                yield $entity => $tuple;
            }
        }
    }

    /**
     * Close opened iterator
     */
    public function closeIterator(): void
    {
        $this->iterating = false;
        $this->priorityEnabled = false;
        $this->priorityAutoAttach = false;
        unset($this->priorityStorage, $this->unprocessed);
        // $this->all = new SplObjectStorage();
    }

    /**
     * Make snapshot: create Node, State if not exists. Also attach Mapper
     */
    private function snap(Tuple $tuple, bool $forceUpdateState = false): void
    {
        $entity = $tuple->entity;
        /** @var Node|null $node */
        $node = $tuple->node ?? $this->orm->getHeap()->get($entity);

        if (($node === null && $tuple->task !== Tuple::TASK_STORE) || $entity instanceof ReferenceInterface) {
            return;
        }
        $tuple->mapper ??= $this->orm->getMapper($node?->getRole() ?? $entity);
        if ($node === null) {
            // Create new Node
            $node = new Node(Node::NEW, [], $tuple->mapper->getRole());
            if (isset($tuple->state)) {
                $tuple->state->setData($tuple->mapper->fetchFields($entity));
                $node->setState($tuple->state);
            }
            $this->orm->getHeap()->attach($entity, $node);
        }
        $tuple->node = $node;
        if (!isset($tuple->state)) {
            $tuple->state = $tuple->node->createState();
            $tuple->state->setData($tuple->mapper->fetchFields($entity));
        } elseif ($forceUpdateState) {
            $tuple->state->setData($tuple->mapper->fetchFields($entity));
        }

        // Backup State
        if (!$this->iterating) {
            $tuple->persist = clone $tuple->state;
        }
    }

    private function trashIt(object $entity, Tuple $tuple, SplObjectStorage $storage): void
    {
        $storage->detach($entity);

        if ($tuple->status === Tuple::STATUS_UNPROCESSED) {
            $tuple->status = Tuple::STATUS_PREPROCESSED;
            $this->unprocessed[] = $tuple;
            return;
        }

        if ($tuple->status >= Tuple::STATUS_PREPROCESSED) {
            $tuple->status = Tuple::STATUS_PROCESSED;
            ++$this->happens;
            return;
        }

        if ($tuple->status % 2 === 0) {
            ++$tuple->status;
            ++$this->happens;
        }
        $this->storage->attach($tuple->entity, $tuple);
    }

    private function activatePriorityStorage(): void
    {
        if ($this->priorityEnabled === true) {
            return;
        }
        $this->priorityEnabled = true;
        $this->priorityStorage = new SplObjectStorage();
    }

    private function updateTuple(Tuple $tuple, int $task, ?int $status, bool $cascade, ?Node $node, ?State $state): void
    {
        if ($status !== null && $tuple->status !== $status) {
            if ($status === Tuple::STATUS_PROCESSED) {
                $this->storage->detach($tuple->entity);
                return;
            }
            if ($tuple->status === Tuple::STATUS_PREPARING) {
                $tuple->status = $status;
            }
        }
        if ($tuple->task !== $task) {
            if ($tuple->task === Tuple::TASK_DELETE) {
                $tuple->task = $task;
            } elseif ($task === Tuple::TASK_FORCE_DELETE) {
                $tuple->task = $task;
            }
        }

        $tuple->cascade = $tuple->cascade || $cascade;
        $node === null or $tuple->node = $tuple->node ?? $node;
        $state === null or $tuple->state = $tuple->state ?? $state;
    }
}
