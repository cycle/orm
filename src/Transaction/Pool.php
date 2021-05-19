<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use IteratorAggregate;
use JetBrains\PhpStorm\ExpectedValues;
use SplObjectStorage;
use Traversable;

final class Pool implements IteratorAggregate, \Countable
{
    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $storage;
    private bool $priorityEnabled = false;
    private bool $priorityAutoAttach = false;
    private SplObjectStorage $priorityStorage;

    public function __construct()
    {
        $this->storage = new SplObjectStorage();
    }
    public function attach(
        object $entity,
        #[ExpectedValues(valuesFromClass: Tuple::class)]
        int $task,
        bool $cascade,
        Node $node = null,
        State $state = null,
        int $status = null
    ): Tuple {
        # Find existing
        $tuple = $this->findTuple($entity);
        if ($tuple !== null) {
            $this->updateTuple($tuple, $task, $status, $cascade, $node, $state);
            return $tuple;
        }

        $tuple = new Tuple($task, $entity, $cascade, $node, $state, $status ?? Tuple::STATUS_PREPARING);
        $this->smartAttachTuple($tuple);
        return $tuple;
    }

    public function attachTuple(Tuple $tuple): void
    {
        # Find existing
        $found = $this->findTuple($tuple->entity);
        if ($found !== null) {
            $this->updateTuple($found, $tuple->task, $tuple->status, $tuple->cascade, $tuple->node, $tuple->state);
            return;
        }
        $this->smartAttachTuple($tuple);
    }

    private function smartAttachTuple(Tuple $tuple): void
    {
        if ($tuple->status === Tuple::STATUS_PROCESSED) {
            return;
        }
        if ($tuple->node !== null) {
            switch ($tuple->task) {
                case Tuple::TASK_DELETE:
                    $tuple->node->setStatus(Node::SCHEDULED_DELETE);
            }
        }
        $string = sprintf(
            "pool:attach %s, task: %s, status: %s\n",
            $tuple->node === null ? get_class($tuple->entity) : $tuple->node->getRole(),
            $tuple->task,
            $tuple->status
        );
        if ($this->priorityAutoAttach && $tuple->status === Tuple::STATUS_PREPARING) {
            echo "\033[90mWith priority $string\033[0m";
            $this->priorityStorage->attach($tuple->entity, $tuple);
        } else {
            echo "\033[90m$string\033[0m";
            $this->storage->attach($tuple->entity, $tuple);
        }
    }

    public function attachStore(
        object $entity,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null
    ): Tuple {
        return $this->attach($entity, Tuple::TASK_STORE, $cascade, $node, $state);
    }

    public function attachDelete(
        object $entity,
        bool $cascade,
        ?Node $node = null,
        ?State $state = null
    ): Tuple {
        return $this->attach($entity, Tuple::TASK_DELETE, $cascade, $node, $state);
    }
    public function offsetGet(object $entity): array
    {
        return $this->storage->offsetGet($entity);
    }

    /**
     * Smart iterator
     *
     * @return Traversable<object, Tuple>
     */
    public function getIterator(): Traversable
    {
        $this->activatePriorityStorage();
        $stage = 0;
        do {
            // High priority first
            if ($this->priorityStorage->count() > 0) {
                $priorityStorage = $this->priorityStorage;
                // $this->priorityStorage = new SplObjectStorage();
                foreach ($priorityStorage as $entity) {
                    // yield $entity => $priorityStorage->offsetGet($entity);
                    $toYield = $priorityStorage->offsetGet($entity);
                    $this->priorityStorage->detach($entity);
                    $this->priorityStorage->offsetUnset($entity);
                    yield $entity => $toYield;
                }
                continue;
            }
            // Other
            if ($this->storage->count() === 0) {
                break;
            }
            $pool = $this->storage;
            // $this->storage = new SplObjectStorage();
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
                    $this->storage->detach($entity);
                    yield $entity => $tuple;
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
                $this->priorityAutoAttach = true;
                $stage = 1;
                echo "\033[90mPOOL_STAGE $stage\033[0m\n";
                $this->storage->rewind();
            }
            if ($stage === 1) {
                // foreach ($pool as $entity) {
                while ($pool->valid()) {
                    /** @var Tuple $tuple */
                    $entity = $pool->current();
                    $tuple = $pool->getInfo();
                    $pool->next();
                    if ($tuple->status !== Tuple::STATUS_WAITING || $tuple->task === Tuple::TASK_DELETE) {
                        continue;
                    }
                    $tuple->status = Tuple::STATUS_WAITED;
                    $this->storage->detach($entity);
                    yield $entity => $tuple;
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
                $stage = 2;
                echo "\033[90mPOOL_STAGE $stage\033[0m\n";
                $this->storage->rewind();
            }
            if ($stage === 2) {
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
                    $this->storage->detach($entity);
                    yield $entity => $tuple;
                    // Check priority
                    if ($this->priorityStorage->count() > 0) {
                        continue 2;
                    }
                }
            }
        } while (true);
        $this->priorityEnabled = false;
        $this->priorityAutoAttach = false;
        unset($this->priorityStorage);
    }
    public function count(): int
    {
        return count($this->storage) + ($this->priorityEnabled ? $this->priorityStorage->count() : 0);
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