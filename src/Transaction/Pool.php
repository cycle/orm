<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use IteratorAggregate;
use JetBrains\PhpStorm\ExpectedValues;
use SplObjectStorage;
use Traversable;

final class Pool implements IteratorAggregate, \Countable
{
    public const DEBUG = false;

    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $storage;

    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $all;

    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $priorityStorage;

    /** @var Tuple[] */
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
        #[ExpectedValues(valuesFromClass: Tuple::class)]
        int $task,
        bool $cascade,
        Node $node = null,
        State $state = null,
        int $status = null,
        bool $highPriority = false
    ): Tuple {
        // Find existing
        $tuple = $this->offsetGet($entity);
        if ($tuple !== null) {
            $this->updateTuple($tuple, $task, $status, $cascade, $node, $state);
            return $tuple;
        }

        $tuple = new Tuple($task, $entity, $cascade, $node, $state, $status ?? Tuple::STATUS_PREPARING);
        return $this->smartAttachTuple($tuple, $highPriority);
    }

    public function attachTuple(Tuple $tuple): void
    {
        // Find existing
        $found = $this->findTuple($tuple->entity);
        if ($found !== null) {
            $this->updateTuple($found, $tuple->task, $tuple->status, $tuple->cascade, $tuple->node, $tuple->state);
            return;
        }
        $this->smartAttachTuple($tuple);
    }

    private function smartAttachTuple(Tuple $tuple, bool $highPriority = false): Tuple
    {
        if ($tuple->status === Tuple::STATUS_PROCESSED) {
            $this->all->attach($tuple->entity, $tuple);
            return $tuple;
        }
        if ($tuple->status === Tuple::STATUS_PREPARING && $this->all->contains($tuple->entity)) {
            return $this->all->offsetGet($tuple->entity);
        }
        $this->all->attach($tuple->entity, $tuple);

        if ($this->iterating) {
            $this->snap($tuple);
        }
        if ($tuple->node !== null) {
            switch ($tuple->task) {
                case Tuple::TASK_DELETE:
                    $tuple->node->setStatus(Node::SCHEDULED_DELETE);
            }
        }
        $string = sprintf(
            "pool:attach %s, task: %s, status: %s\n",
            $tuple->node === null ? $tuple->entity::class : $tuple->node->getRole(),
            $tuple->task,
            $tuple->status
        );
        if (($this->priorityAutoAttach || $highPriority) && $tuple->status === Tuple::STATUS_PREPARING) {
            self::DEBUG && print "\033[90mWith priority $string\033[0m";
            $this->priorityStorage->attach($tuple->entity, $tuple);
        } else {
            self::DEBUG && print "\033[90m$string\033[0m";
            $this->storage->attach($tuple->entity, $tuple);
        }
        return $tuple;
    }

    public function attachStore(
        object $entity,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null,
        bool $highPriority = false
    ): Tuple {
        return $this->attach($entity, Tuple::TASK_STORE, $cascade, $node, $state, null, $highPriority);
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
     * @return Traversable<mixed, Tuple>
     */
    public function getIterator(): Traversable
    {
        if ($this->iterating) {
            throw new \RuntimeException('The Pool is now iterating.');
        }
        $this->iterating = true;
        $this->activatePriorityStorage();
        $this->unprocessed = [];

        // Snap all entities before store
        while ($this->storage->valid()) {
            /** @var Tuple $tuple */
            $tuple = $this->storage->getInfo();
            $this->snap($tuple);
            if ($tuple->node === null) {
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
                self::DEBUG && print "\033[90mPOOL_STAGE $stage\033[0m\n";
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
                self::DEBUG && print "\033[90mPOOL_STAGE $stage\033[0m\n";
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
                if ($this->happens !== 0 && $this->unprocessed !== []) {
                    foreach ($this->unprocessed as $item) {
                        $this->storage->attach($item->entity, $item);
                    }
                    $this->unprocessed = [];
                    continue;
                }
                if ($this->happens === 0 && \count($pool) > 0) {
                    throw new \Exception('Transaction can not be resolved.');
                }
            }
        } while (true);
        $this->iterating = false;
        $this->priorityEnabled = false;
        $this->priorityAutoAttach = false;
        $this->unprocessed = [];
        unset($this->priorityStorage, $this->unprocessed);
        $this->all = new SplObjectStorage();
    }

    public function count(): int
    {
        return \count($this->storage) + ($this->priorityEnabled ? $this->priorityStorage->count() : 0);
    }

    /**
     * Make snapshot: create Node, State if not exists. Also attach Mapper
     */
    private function snap(Tuple $tuple): void
    {
        $entity = $tuple->entity;
        $tuple->node ??= $this->orm->getHeap()->get($entity);
        if (($tuple->node === null && $tuple->task !== Tuple::TASK_STORE) || $entity instanceof ReferenceInterface) {
            return;
        }
        $tuple->mapper ??= $this->orm->getMapper($tuple->node?->getRole() ?? $entity);
        if ($tuple->node === null) {
            $node = new Node(Node::NEW, [], $tuple->mapper->getRole());
            $this->orm->getHeap()->attach($entity, $node);
            $node->setData($tuple->mapper->fetchFields($entity));
            $tuple->node = $node;
        } elseif (!$tuple->node->hasState()) {
            $tuple->node->setData($tuple->mapper->fetchFields($entity));
        }
        $tuple->state ??= $tuple->node->getState();
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
        $tuple->node = $tuple->node ?? $node;
        $tuple->state = $tuple->state ?? $state;
    }

    private function findTuple(object $entity): ?Tuple
    {
        if ($this->priorityEnabled && $this->priorityStorage->contains($entity)) {
            return $this->priorityStorage->offsetGet($entity);
        }
        if ($this->storage->contains($entity)) {
            return $this->storage->offsetGet($entity);
        }
        return null;
    }
}
