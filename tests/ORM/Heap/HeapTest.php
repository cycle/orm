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
        INDEX_FIELDS_1 = 'id';
    protected const
        INDEX_VALUES_1_1 = 42;
    protected const
        INDEX_VALUES_1_2 = 24;
    protected const
        INDEX_FIND_1_1 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1];
    protected const
        INDEX_FIND_1_2 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_2];
    protected const
        INDEX_FIND_1_BAD = [self::INDEX_FIELDS_1 => 404];
    protected const
        INDEX_FIELDS_2 = 'email';
    protected const
        INDEX_VALUES_2_1 = 'mail1@spiral';
    protected const
        INDEX_VALUES_2_2 = 'mail2@spiral';
    protected const
        INDEX_FIND_2_1 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1];
    protected const
        INDEX_FIND_2_2 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2];
    protected const
        INDEX_FIND_2_BAD = [self::INDEX_FIELDS_2 => 505];
    protected const
        INDEX_FIELDS_BAD = 'foo';
    protected const
        INDEX_FIND_BAD = [self::INDEX_FIELDS_BAD => null];
    protected const
        ENTITY_SET_1 = [
            self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1,
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1,
        ];
    protected const
        ENTITY_SET_2 = [
            self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_2,
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2,
        ];

    public function testAttachAndFind(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertSame($entity2, $heap->find('user', static::INDEX_FIND_1_2), 'Found');
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_BAD), 'Not found');
        $this->assertNull($heap->find('user', []), 'Empty scope');
    }

    public function testIndexAsArray(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [(array)static::INDEX_FIELDS_1]);

        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_1_1), 'Found');
    }

    public function testFindByNotExistingIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    public function testAttachWithEmptyIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [[], static::INDEX_FIELDS_1]);

        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_1_1), 'Found');
        $this->assertNull($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    public function testAttachWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_BAD, static::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_2_1), 'Found');

        // No error
        $heap->detach($entity);
    }

    public function testFindWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_2_1), 'Found');
    }

    public function testFindBadRole(): void
    {
        $heap = $this->createHeap();

        $this->assertNull($heap->find('bad_role', static::INDEX_FIND_BAD));
    }

    public function testFindEmptyCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', []));
    }

    public function testDetach(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::INDEX_FIND_1_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertSame($entity1, $heap->find('user', static::INDEX_FIND_1_1), 'Found');
        $this->assertSame($entity2, $heap->find('user', static::INDEX_FIND_1_2), 'Found');
        $this->assertTrue($heap->has($entity1));
        $this->assertTrue($heap->has($entity2));

        // Now detach it
        $heap->detach($entity1);
        $heap->detach($entity2);

        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_2));
        $this->assertFalse($heap->has($entity1));
        $this->assertFalse($heap->has($entity2));
    }

    public function testNotEntity(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $this->assertTrue($heap->has($entity));

        $heap->detach(new \stdClass());

        $this->assertTrue($heap->has($entity));
    }

    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertSame($node1, $heap->get($entity1));
        $this->assertSame($node2, $heap->get($entity2));
    }

    public function testClean(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        // Now detach it
        $heap->clean();

        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
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
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        foreach (static::ENTITY_SET_2 as $key => $value) {
            $node->getState()->register($key, $value);
        }
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_1_2));
    }

    public function testSyncWhenEntitiesPKSwitch(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        foreach (static::ENTITY_SET_2 as $key => $value) {
            $node1->getState()->register($key, $value);
        }
        foreach (static::ENTITY_SET_1 as $key => $value) {
            $node2->getState()->register($key, $value);
        }
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertSame($entity1, $heap->find('user', static::INDEX_FIND_1_2));
        $this->assertSame($entity2, $heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testOverwriteEntity(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);

        $node2 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertSame($entity2, $heap->find('user', static::INDEX_FIND_1_1));

        $heap->detach($entity1);

        $this->assertSame($entity2, $heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testEditNodeStateWithConflict(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $node1->setData(static::ENTITY_SET_2);
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);

        // entity2 has been overwritten
        $this->assertSame($entity1, $heap->find('user', static::INDEX_FIND_1_2));
        // old value has been detached
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testEditNodeStateNullPKAndSync(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $values = [];
        foreach (static::ENTITY_SET_1 as $field => $value) {
            $values[$field] = null;
        }
        $node->setData($values);
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $this->assertTrue($heap->has($entity));
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testEditNodeStateNullPKAndDetach(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $values = [];
        foreach (static::ENTITY_SET_1 as $field => $value) {
            $values[$field] = null;
        }
        $node->setData($values);
        $heap->detach($entity);

        $this->assertFalse($heap->has($entity));
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testEmptyNode(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, [], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $this->assertTrue($heap->has($entity2));
        $this->assertTrue($heap->has($entity1));

        $heap->detach($entity2);
        $heap->detach($entity1);

        $this->assertFalse($heap->has($entity2));
        $this->assertFalse($heap->has($entity1));
    }

    public function testAttachWithNewIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_2_1));

        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_2_1));
        // old index was not deleted
        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_1_1));
    }

    public function testSyncWhenIndexAndValuesChanged(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        foreach (static::ENTITY_SET_2 as $key => $value) {
            $node->getState()->register($key, $value);
        }
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', static::INDEX_FIND_1_1));
        $this->assertNull($heap->find('user', static::INDEX_FIND_1_2));
        $this->assertNull($heap->find('user', static::INDEX_FIND_2_1));
        $this->assertSame($entity, $heap->find('user', static::INDEX_FIND_2_2));
    }

    protected function createHeap(): HeapInterface
    {
        return new Heap();
    }
}
