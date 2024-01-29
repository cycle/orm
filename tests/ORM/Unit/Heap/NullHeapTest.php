<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\NullHeap;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

final class NullHeapTest extends TestCase
{
    protected const INDEX_FIELDS_1 = 'id';
    protected const INDEX_VALUES_1_1 = 42;
    protected const INDEX_VALUES_1_2 = 24;
    protected const INDEX_FIND_1_1 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1];
    protected const INDEX_FIND_1_2 = [self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_2];
    protected const INDEX_FIND_1_BAD = [self::INDEX_FIELDS_1 => 404];
    protected const INDEX_FIELDS_2 = 'email';
    protected const INDEX_VALUES_2_1 = 'mail1@spiral';
    protected const INDEX_VALUES_2_2 = 'mail2@spiral';
    protected const INDEX_FIND_2_1 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1];
    protected const INDEX_FIELDS_BAD = 'foo';
    protected const INDEX_FIND_BAD = [self::INDEX_FIELDS_BAD => null];
    protected const ENTITY_SET_1 = [
        self::INDEX_FIELDS_1 => self::INDEX_VALUES_1_1,
        self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1,
    ];
    protected const ENTITY_SET_2 = [
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

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_2));
        $this->assertNull($heap->find('user', self::INDEX_FIND_1_BAD));
        $this->assertNull($heap->find('user', []));
    }

    public function testIndexAsArray(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [(array)self::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
    }

    public function testFindByNotExistingIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));
    }

    public function testAttachWithEmptyIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [[], self::INDEX_FIELDS_1]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_1_1));
        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));
    }

    public function testAttachWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_BAD, self::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_BAD));
        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));

        // No error
        $heap->detach($entity);
    }

    public function testFindWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', self::INDEX_FIND_BAD));
        $this->assertNull($heap->find('user', self::INDEX_FIND_2_1));
    }

    public function testFindBadRole(): void
    {
        $heap = $this->createHeap();

        $this->assertNull($heap->find('bad_role', self::INDEX_FIND_BAD));
    }

    public function testFindEmptyCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        $this->assertNull($heap->find('user', []));
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

        $this->assertNull($heap->get($entity1));
        $this->assertNull($heap->get($entity2));
    }

    public function testClean(): void
    {
        $this->createHeap()->clean();

        $this->assertTrue(true, 'There are no exceptions');
    }

    protected function createHeap(): HeapInterface
    {
        return new NullHeap();
    }
}
