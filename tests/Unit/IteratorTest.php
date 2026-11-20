<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Iterator;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Service\EntityFactoryInterface;
use Mockery as m;
use Testo\Assert;
use Testo\Codecov\Covers;
use Testo\Test;

#[Test]
#[Covers(Iterator::class)]
final class IteratorTest
{
    public function roleFromRowOverridesIteratorRole(): void
    {
        $factory = m::mock(EntityFactoryInterface::class);
        $admin = new \stdClass();
        $factory->expects('make')->with('admin', m::any(), Node::MANAGED, false)->andReturn($admin);
        $iterator = Iterator::createWithServices(new Heap(), $this->schema(), $factory, 'user', [
            [LoaderInterface::ROLE_KEY => 'admin', 'id' => 1],
        ]);

        Assert::same(\iterator_to_array($iterator), [$admin]);
    }

    public function entityIsTakenFromHeapByPrimaryKey(): void
    {
        $heap = new Heap();
        $known = new \stdClass();
        $heap->attach($known, new Node(Node::MANAGED, ['id' => 1], 'user'), ['id']);
        $created = new \stdClass();
        $factory = m::mock(EntityFactoryInterface::class);
        $factory->expects('make')->with('user', ['id' => 2], Node::MANAGED, false)->andReturn($created);
        $iterator = Iterator::createWithServices($heap, $this->schema(), $factory, 'user', [
            ['id' => 1],
            ['id' => 2],
        ], findInHeap: true);

        Assert::same(\iterator_to_array($iterator), [$known, $created]);
    }

    private function schema(): Schema
    {
        return new Schema([
            'user' => [SchemaInterface::PRIMARY_KEY => 'id'],
            'admin' => [SchemaInterface::PRIMARY_KEY => 'id'],
        ]);
    }
}
