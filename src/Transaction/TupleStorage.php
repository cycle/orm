<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Countable;
use IteratorAggregate;
use SplObjectStorage;

/**
 * @internal
 * @implements IteratorAggregate<object, Tuple>
 */
final class TupleStorage implements IteratorAggregate, Countable
{
    /** @var SplObjectStorage<object, Tuple> */
    private SplObjectStorage $storage;

    public function __construct()
    {
        $this->storage = new SplObjectStorage();
    }

    public function getIterator(): \Traversable
    {
        $this->storage->rewind();

        while ($this->storage->valid()) {
            $entity = $this->storage->current();
            $tuple = $this->storage->getInfo();
            $this->storage->next();
            yield $entity => $tuple;
        }
    }

    /**
     * Returns {@see Tuple} if exists, throws an exception otherwise.
     *
     * @throws \Throwable if the entity is not found in the storage
     */
    public function getTuple(object $entity): Tuple
    {
        return $this->storage->offsetGet($entity);
    }

    public function attach(Tuple $tuple): void
    {
        $this->storage->attach($tuple->entity, $tuple);
    }

    public function contains(object $entity): bool
    {
        return $this->storage->contains($entity);
    }

    public function detach(object $entity): void
    {
        $this->storage->offsetUnset($entity);
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return $this->storage->count();
    }
}
