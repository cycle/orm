<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Heap;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class HeapTest extends TestCase
{
    protected const
        INDEX_FIELDS_1 = 'id',
        INDEX_VALUES_1_1 = 42,
        INDEX_VALUES_1_2 = 24,
        INDEX_FIND_1_1 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1],
        INDEX_FIND_1_2 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_2],
        INDEX_FIND_1_BAD = [self::INDEX_FIELDS_1 => 404],

        INDEX_FIELDS_2 = 'email',
        INDEX_VALUES_2_1 = 'mail1@spiral',
        INDEX_VALUES_2_2 = 'mail2@spiral',
        INDEX_FIND_2_1 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1],
        INDEX_FIND_2_2 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2],
        INDEX_FIND_2_BAD = [self::INDEX_FIELDS_2 => 505],

        ENTITY_SET_1 = [
            self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1,
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1,
        ],
        ENTITY_SET_2 = [
            self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_2,
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2,
        ];

    public function testAttachAndFind(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        $this->assertSame($entity2, $heap->find('user', self::INDEX_FIND_1_2), 'Found');
        $this->assertNull($heap->find('user', self::INDEX_FIND_1_BAD), 'Not found');
        $this->assertNull($heap->find('user', []), 'Empty scope');
    }

    public function testDetach(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, self::INDEX_FIND_1_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::INDEX_FIND_1_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        $this->assertSame($entity1, $heap->find('user', self::INDEX_FIND_1_1), 'Found');
        $this->assertSame($entity2, $heap->find('user', self::INDEX_FIND_1_2), 'Found');
        $this->assertTrue($heap->has($entity1));
        $this->assertTrue($heap->has($entity2));

        # Now detach it
        $heap->detach($entity1);
        $heap->detach($entity2);

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
        $this->assertNull($heap->find('user', self::INDEX_FIND_1_2));
        $this->assertFalse($heap->has($entity1));
        $this->assertFalse($heap->has($entity2));
    }

    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        $this->assertSame($node1, $heap->get($entity1));
        $this->assertSame($node2, $heap->get($entity2));
    }

    public function testClean(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        # Now detach it
        $heap->clean();

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
        $this->assertFalse($heap->has($entity1));
        $this->assertFalse($heap->has($entity2));

        $count = 0;
        foreach ($heap as $value) {
            ++$count;
        }
        $this->assertSame(0, $count);
    }

    public function testSyncWhenIndexedValueChanged(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        foreach (self::ENTITY_SET_2 as $key => $value) {
            $node->getState()->register($key, $value);
        }
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
        $this->assertSame($entity, $heap->find('user', self::INDEX_FIND_1_2));
    }

    public function testSyncWhenEntitiesPKSwitch(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        foreach (self::ENTITY_SET_2 as $key => $value) {
            $node1->getState()->register($key, $value);
        }
        foreach (self::ENTITY_SET_1 as $key => $value) {
            $node2->getState()->register($key, $value);
        }
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        $this->assertSame($entity1, $heap->find('user', self::INDEX_FIND_1_2));
        $this->assertSame($entity2, $heap->find('user', self::INDEX_FIND_1_1));
    }

    public function testOverwriteEntity(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);

        $node2 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        $this->assertSame($entity2, $heap->find('user', self::INDEX_FIND_1_1));
    }

    public function testAttachWithNewIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));

        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        $this->assertSame($entity, $heap->find('user', self::INDEX_FIND_2_1));
        // old index was not deleted
        $this->assertSame($entity, $heap->find('user', self::INDEX_FIND_1_1));
    }

    public function testSyncWhenIndexAndValuesChanged(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        foreach (self::ENTITY_SET_2 as $key => $value) {
            $node->getState()->register($key, $value);
        }
        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
        $this->assertNull($heap->find('user', self::INDEX_FIND_1_2));
        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));
        $this->assertSame($entity, $heap->find('user', self::INDEX_FIND_2_2));
    }

    protected function createHeap(): HeapInterface
    {
        return new Heap();
    }
}
