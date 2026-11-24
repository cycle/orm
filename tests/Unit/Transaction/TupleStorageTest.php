<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\ORM\Transaction\Tuple;
use Cycle\ORM\Transaction\TupleStorage;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

#[Test]
final class TupleStorageTest
{
    public function testNotAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $entity = new \stdClass();

        Assert::count($storage, 0);
        Assert::false($storage->contains($entity));
        Expect::exception(\Throwable::class);
        $storage->getTuple(new \stdClass());
    }

    public function testAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $tuple = $this->createTuple($entity = new \stdClass());

        $storage->attach($tuple);

        Assert::count($storage, 1);
        Assert::true($storage->contains($entity));
        Assert::same($storage->getTuple($entity), $tuple);

        $storage->detach($entity);

        Assert::count($storage, 0);
        Assert::false($storage->contains($entity));
        Expect::exception(\Throwable::class);
        $storage->getTuple($entity);
    }

    public function firstTupleOfEntityIsKept(): void
    {
        $storage = new TupleStorage();
        $entity = new \stdClass();
        $first = $this->createTuple($entity);

        $storage->attach($first);
        $storage->attach($this->createTuple($entity));

        Assert::count($storage, 1);
        Assert::same($storage->getTuple($entity), $first);
    }

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

        Assert::count($storage, \count($tuples));
        foreach ($tuples as $tuple) {
            Assert::true($storage->contains($tuple->entity));
            Assert::same($storage->getTuple($tuple->entity), $tuple);
        }

        $collection = [];
        foreach ($storage as $entity => $tuple) {
            Assert::same($tuple->entity, $entity);
            $collection[] = $tuple;
        }

        Assert::same($collection, \array_values($tuples));
    }

    public function testAddItemsWhenIterating(): void
    {
        $storage = new TupleStorage();
        for ($i = 0; $i < 10; $i++) {
            $storage->attach($this->createTuple(new \stdClass()));
        }

        /** @see TupleStorage::$iterators */
        Assert::count((fn(): array => $this->iterators)->call($storage), 0);

        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            break;
        }
        /** @see TupleStorage::$iterators */
        Assert::count((fn(): array => $this->iterators)->call($storage), 1);

        // Cleanup on iterator destruction
        unset($iterator);
        /** @see TupleStorage::$iterators */
        Assert::count((fn(): array => $this->iterators)->call($storage), 0);

        // Cleanup on end of iteration
        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            // do nothing
        }
        /** @see TupleStorage::$iterators */
        Assert::count((fn(): array => $this->iterators)->call($storage), 0);
    }

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
            Assert::true($storage->contains($tuple->entity));
            Assert::same($storage->getTuple($tuple->entity), $tuple);

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        Assert::count($storage, 3);
        Assert::same($collection, [$tuple1, $tuple2, $tuple4]);
    }

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
            Assert::true($storage->contains($tuple->entity));
            Assert::same($storage->getTuple($tuple->entity), $tuple);

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        Assert::count($storage, 3);
        Assert::same($collection, [$tuple1, $tuple2, $tuple4]);
    }

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
            Assert::true($iterator1->valid());
            Assert::same($iterator1->current(), $tuple);

            if (++$i % 2 === 0) {
                $storage->attach($this->createTuple(new \stdClass()));
            }
            $iterator1->next();
        }
    }

    public function missingTupleIsReportedWithRuntimeException(): never
    {
        $storage = new TupleStorage();

        Expect::exception(\RuntimeException::class)->withMessage('Tuple not found');

        $storage->getTuple(new \stdClass());
    }

    private function createTuple(object $entity): Tuple
    {
        return new Tuple(Tuple::TASK_STORE, $entity, true, Tuple::STATUS_PREPARING);
    }
}
