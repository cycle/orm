<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\ORM\Transaction\Tuple;
use Cycle\ORM\Transaction\TupleStorage;
use PHPUnit\Framework\TestCase;

class TupleStorageTest extends TestCase
{
    public function testNotAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $entity = new \stdClass();

        $this->assertCount(0, $storage);
        $this->assertFalse($storage->contains($entity));
        $this->expectException(\Throwable::class);
        $storage->getTuple(new \stdClass());
    }

    public function testAttachedEntity(): void
    {
        $storage = new TupleStorage();
        $tuple = $this->createTuple($entity = new \stdClass());

        $storage->attach($tuple);

        self::assertCount(1, $storage);
        self::assertTrue($storage->contains($entity));
        self::assertSame($tuple, $storage->getTuple($entity));

        $storage->detach($entity);

        self::assertCount(0, $storage);
        self::assertFalse($storage->contains($entity));
        $this->expectException(\Throwable::class);
        $storage->getTuple($entity);
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

        self::assertCount(\count($tuples), $storage);
        foreach ($tuples as $tuple) {
            self::assertTrue($storage->contains($tuple->entity));
            self::assertSame($tuple, $storage->getTuple($tuple->entity));
        }

        $collection = [];
        foreach ($storage as $entity => $tuple) {
            self::assertSame($entity, $tuple->entity);
            $collection[] = $tuple;
        }

        self::assertSame(\array_values($tuples), $collection);
    }

    public function testAddItemsWhenIterating(): void
    {
        $storage = new TupleStorage();
        for ($i = 0; $i < 10; $i++) {
            $storage->attach($this->createTuple(new \stdClass()));
        }

        /** @see TupleStorage::$iterators */
        self::assertCount(0, (fn(): array => $this->iterators)->call($storage));

        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            break;
        }
        /** @see TupleStorage::$iterators */
        self::assertCount(1, (fn(): array => $this->iterators)->call($storage));

        // Cleanup on iterator destruction
        unset($iterator);
        /** @see TupleStorage::$iterators */
        self::assertCount(0, (fn(): array => $this->iterators)->call($storage));

        // Cleanup on end of iteration
        $iterator = $storage->getIterator();
        // Start generator
        foreach ($iterator as $item) {
            // do nothing
        }
        /** @see TupleStorage::$iterators */
        self::assertCount(0, (fn(): array => $this->iterators)->call($storage));
    }

    public function testDetachWhenIterating(): void
    {
        $storage = new TupleStorage();
        $tuple1 = $this->createTuple((object)['value' => 1]);
        $tuple2 = $this->createTuple((object)['value' => 2]);
        $tuple3 = $this->createTuple((object)['value' => 3]);
        $tuple4 = $this->createTuple((object)['value' => 4]);

        $storage->attach($tuple1);
        $storage->attach($tuple2);
        $storage->attach($tuple3);
        $storage->attach($tuple4);

        $collection = [];
        foreach ($storage as $tuple) {
            $collection[] = $tuple;
            self::assertTrue($storage->contains($tuple->entity));
            self::assertSame($tuple, $storage->getTuple($tuple->entity));

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        self::assertCount(3, $storage);
        self::assertSame([$tuple1, $tuple2, $tuple4], $collection);
    }

    public function testCleanupIteratorState(): void
    {
        $storage = new TupleStorage();
        $tuple1 = $this->createTuple((object)['value' => 1]);
        $tuple2 = $this->createTuple((object)['value' => 2]);
        $tuple3 = $this->createTuple((object)['value' => 3]);
        $tuple4 = $this->createTuple((object)['value' => 4]);

        $storage->attach($tuple1);
        $storage->attach($tuple2);
        $storage->attach($tuple3);
        $storage->attach($tuple4);

        $collection = [];
        foreach ($storage as $tuple) {
            $collection[] = $tuple;
            self::assertTrue($storage->contains($tuple->entity));
            self::assertSame($tuple, $storage->getTuple($tuple->entity));

            if ($tuple === $tuple2) {
                $storage->detach($tuple3->entity);
            }
        }
        self::assertCount(3, $storage);
        self::assertSame([$tuple1, $tuple2, $tuple4], $collection);
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
            self::assertTrue($iterator1->valid());
            self::assertSame($tuple, $iterator1->current());

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
