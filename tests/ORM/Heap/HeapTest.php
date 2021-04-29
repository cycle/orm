<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Heap;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

final class HeapTest extends TestCase
{
    public function testAttachAndFind(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, ['id' => 42, 'email' => 'test2'], 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, ['id']);
        $heap->attach($entity2, $node2, ['id']);

        $this->assertSame($entity2, $heap->find('user', ['id' => 42]), 'Found');
        $this->assertNull($heap->find('user', ['id' => 1]), 'Not found');
        $this->assertNull($heap->find('user', []), 'Empty scope');
    }

    public function testDetach(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, ['id' => 42, 'email' => 'test2'], 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, ['id']);
        $heap->attach($entity2, $node2, ['id']);

        $this->assertSame($entity2, $heap->find('user', ['id' => 42]), 'Found');
        $this->assertTrue($heap->has($entity1));
        $this->assertTrue($heap->has($entity2));

        # Now detach it
        $heap->detach($entity1);
        $heap->detach($entity2);

        $this->assertNull($heap->find('user', ['id' => 42]));
        $this->assertFalse($heap->has($entity1));
        $this->assertFalse($heap->has($entity2));
    }

    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, ['id' => 42, 'email' => 'test2'], 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, ['id']);
        $heap->attach($entity2, $node2, ['id']);

        $this->assertSame($node1, $heap->get($entity1));
        $this->assertSame($node2, $heap->get($entity2));
    }

    public function testClean(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, ['id' => 42, 'email' => 'test2'], 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, ['id']);
        $heap->attach($entity2, $node2, ['id']);

        # Now detach it
        $heap->clean();

        $this->assertNull($heap->find('user', ['id' => 42]));
        $this->assertFalse($heap->has($entity1));
        $this->assertFalse($heap->has($entity2));

        $count = 0;
        foreach ($heap as $value) {
            ++$count;
        }
        $this->assertSame(0, $count);
    }

    protected function createHeap(): HeapInterface
    {
        return new Heap();
    }
}
