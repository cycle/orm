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
        $tuples = [];
        for ($i = 0; $i < 10; $i++) {
            $tuples[] = $this->createTuple(new \stdClass());
        }
        // Randomize the order
        \shuffle($tuples);

        // Store all
        foreach ($tuples as $tuple) {
            $storage->attach($tuple);
        }

        $collection = [];
        $i = 0;
        foreach ($storage as $entity => $tuple) {
            $collection[] = $tuple;
            self::assertTrue($storage->contains($tuple->entity));
            self::assertSame($tuple, $storage->getTuple($tuple->entity));

            // Add a new item after each 10th iteration
            if (++$i % 10 === 0) {
                $newTuple = $this->createTuple(new \stdClass());
                $storage->attach($newTuple);
                $tuples[] = $newTuple;
            }
        }

        self::assertCount(\count($tuples), $storage);
        self::assertCount(\count($tuples), $collection);
        self::assertSame(\array_values($tuples), $collection);
    }

    private function createTuple(object $entity): Tuple
    {
        return new Tuple(Tuple::TASK_STORE, $entity, true, Tuple::STATUS_PREPARING);
    }
}
