<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Countable;
use IteratorAggregate;

/**
 * @internal
 *
 * @implements IteratorAggregate<object, Tuple>
 */
final class TupleStorage implements IteratorAggregate, Countable
{
    /** @var array<int, Tuple> */
    private array $storage = [];

    private array $iterators = [];

    /**
     * @return \Traversable<object, Tuple>
     */
    public function getIterator(): \Traversable
    {
        $iterator = $this->storage;
        // When the generator is destroyed, the reference to the iterator is removed from the collection.
        $cleaner = new class () {
            public array $iterators;

            public function __destruct()
            {
                unset($this->iterators[\spl_object_id($this)]);
            }
        };
        /** @psalm-suppress UnsupportedPropertyReferenceUsage */
        $cleaner->iterators = &$this->iterators;
        $this->iterators[\spl_object_id($cleaner)] = &$iterator;

        while (\count($iterator) > 0) {
            $tuple = \current($iterator);
            unset($iterator[\key($iterator)]);
            yield $tuple->entity => $tuple;
        }
    }

    /**
     * Returns {@see Tuple} if exists, throws an exception otherwise.
     *
     * @throws \Throwable if the entity is not found in the storage
     */
    public function getTuple(object $entity): Tuple
    {
        return $this->storage[\spl_object_id($entity)] ?? throw new \RuntimeException('Tuple not found');
    }

    public function attach(Tuple $tuple): void
    {
        if ($this->contains($tuple->entity)) {
            return;
        }

        $this->storage[\spl_object_id($tuple->entity)] = $tuple;
        foreach ($this->iterators as &$collection) {
            $collection[\spl_object_id($tuple->entity)] = $tuple;
        }
    }

    public function contains(object $entity): bool
    {
        return \array_key_exists(\spl_object_id($entity), $this->storage);
    }

    public function detach(object $entity): void
    {
        $id = \spl_object_id($entity);
        unset($this->storage[$id]);
        foreach ($this->iterators as &$collection) {
            unset($collection[$id]);
        }
    }

    /**
     * @return int<0, max>
     */
    public function count(): int
    {
        return \count($this->storage);
    }
}
