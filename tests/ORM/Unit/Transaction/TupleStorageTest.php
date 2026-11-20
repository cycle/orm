<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\ORM\Transaction\Tuple;
use Cycle\ORM\Transaction\TupleStorage;
use PHPUnit\Framework\TestCase;

class TupleStorageTest
{
    #[\Testo\Test]
    public function testNotAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $entity = new \stdClass();

        \Testo\Assert::count($storage, 0);
        \Testo\Assert::false($storage->contains($entity));
        \Testo\Expect::exception(\Throwable::class);
        $storage->getTuple(new \stdClass());
    }

    #[\Testo\Test]
    public function testAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $tuple = $this->createTuple($entity = new \stdClass());

        $storage->attach($tuple);

        \Testo\Assert::count($storage, 1);
        \Testo\Assert::true($storage->contains($entity));
        \Testo\Assert::same($storage->getTuple($entity), $tuple);

        $storage->detach($entity);

        \Testo\Assert::count($storage, 0);
        \Testo\Assert::false($storage->contains($entity));
        \Testo\Expect::exception(\Throwable::class);
        $storage->getTuple($entity);
    }

    #[\Testo\Test]
    public function testSequence(): void
    {
        $storage = new TupleStorage();
        $tuples = [];
        for ($i = 0; $i < 100; $i++) {
            $tuples[] = $this->createTuple(new \stdClass());
        }
        // Randomize the order
        \shuffle($tuples);

        // Store all
        foreach ($tuples as $tuple) {
            $storage->attach($tuple);
        }
        // and detach each second
        $toRestore = [];
        foreach ($tuples as $k => $tuple) {
            if ($k % 2 === 0) {
                $storage->detach($tuple->entity);
                unset($tuples[$k]);
            }
        }
        // and return each fourth
        foreach ($toRestore as $k => $tuple) {
            if ($k % 2 === 0) {
                $storage->attach($tuple);
                $tuples[] = $tuple;
            }
        }

        \Testo\Assert::count($storage, \count($tuples));
        foreach ($tuples as $tuple) {
            \Testo\Assert::true($storage->contains($tuple->entity));
            \Testo\Assert::same($storage->getTuple($tuple->entity), $tuple);
        }

        $collection = [];
        foreach ($storage as $entity => $tuple) {
            \Testo\Assert::same($tuple->entity, $entity);
            $collection[] = $tuple;
        }

        \Testo\Assert::same($collection, \array_values($tuples));
    }

    #[\Testo\Test]
    public function testAddItemsWhenIterating(): void
    {
        $storage = new TupleStorage();
        for ($i = 0; $i < 10; $i++) {
            $storage->attach($this->createTuple(new \stdClass()));
        }

        /** @see TupleStorage::$iterators */
        \Testo\Assert::count((fn(): array => $this->iterators)->call($storage), 0);

        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            break;
        }
        /** @see TupleStorage::$iterators */
        \Testo\Assert::count((fn(): array => $this->iterators)->call($storage), 1);

        // Cleanup on iterator destruction
        unset($iterator);
        /** @see TupleStorage::$iterators */
        \Testo\Assert::count((fn(): array => $this->iterators)->call($storage), 0);

        // Cleanup on end of iteration
        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            // do nothing
        }
        /** @see TupleStorage::$iterators */
        \Testo\Assert::count((fn(): array => $this->iterators)->call($storage), 0);
    }

    #[\Testo\Test]
    public function testDetachWhenIterating(): void
    {
        $storage = new TupleStorage();
        $tuple1 = $this->createTuple((object) ['value' => 1]);
        $tuple2 = $this->createTuple((object) ['value' => 2]);
        $tuple3 = $this->createTuple((object) ['value' => 3]);
        $tuple4 = $this->createTuple((object) ['value' => 4]);

        $storage->attach($tuple1);
        $storage->attach($tuple2);
        $storage->attach($tuple3);
        $storage->attach($tuple4);

        $collection = [];
        foreach ($storage as $tuple) {
            $collection[] = $tuple;
            \Testo\Assert::true($storage->contains($tuple->entity));
            \Testo\Assert::same($storage->getTuple($tuple->entity), $tuple);

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        \Testo\Assert::count($storage, 3);
        \Testo\Assert::same($collection, [$tuple1, $tuple2, $tuple4]);
    }

    #[\Testo\Test]
    public function testCleanupIteratorState(): void
    {
        $storage = new TupleStorage();
        $tuple1 = $this->createTuple((object) ['value' => 1]);
        $tuple2 = $this->createTuple((object) ['value' => 2]);
        $tuple3 = $this->createTuple((object) ['value' => 3]);
        $tuple4 = $this->createTuple((object) ['value' => 4]);

        $storage->attach($tuple1);
        $storage->attach($tuple2);
        $storage->attach($tuple3);
        $storage->attach($tuple4);

        $collection = [];
        foreach ($storage as $tuple) {
            $collection[] = $tuple;
            \Testo\Assert::true($storage->contains($tuple->entity));
            \Testo\Assert::same($storage->getTuple($tuple->entity), $tuple);

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        \Testo\Assert::count($storage, 3);
        \Testo\Assert::same($collection, [$tuple1, $tuple2, $tuple4]);
    }

    #[\Testo\Test]
    public function testParallelIterators(): void
    {
        $storage = new TupleStorage();
        for ($i = 0; $i < 5; $i++) {
            $tuple = $this->createTuple(new \stdClass());
            $storage->attach($tuple);
        }

        /** @var \Generator $iterator1 */
        $iterator1 = $storage->getIterator();

        $i = 0;
        foreach ($storage as $tuple) {
            \Testo\Assert::true($iterator1->valid());
            \Testo\Assert::same($iterator1->current(), $tuple);

            if (++$i % 2 === 0) {
                $storage->attach($this->createTuple(new \stdClass()));
            }
            $iterator1->next();
        }
    }

    private function createTuple(object $entity): Tuple
    {
        return new Tuple(Tuple::TASK_STORE, $entity, true, Tuple::STATUS_PREPARING);
    }
}
