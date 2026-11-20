<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\NullHeap;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

final class NullHeapTest
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

    #[\Testo\Test]
    public function testAttachAndFind(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_2));
        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_BAD));
        \Testo\Assert::null($heap->find('user', []));
    }

    #[\Testo\Test]
    public function testIndexAsArray(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [(array) self::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_1));
    }

    #[\Testo\Test]
    public function testFindByNotExistingIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_2_1));
    }

    #[\Testo\Test]
    public function testAttachWithEmptyIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [[], self::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_1));
        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_2_1));
    }

    #[\Testo\Test]
    public function testAttachWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_BAD, self::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_BAD));
        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_2_1));

        // No error
        $heap->detach($entity);
    }

    #[\Testo\Test]
    public function testFindWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_BAD));
        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_2_1));
    }

    #[\Testo\Test]
    public function testFindBadRole(): void
    {
        $heap = $this->createHeap();

        \Testo\Assert::null($heap->find('bad_role', self::INDEX_FIND_BAD));
    }

    #[\Testo\Test]
    public function testFindEmptyCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', []));
    }

    #[\Testo\Test]
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

        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_1));
        \Testo\Assert::null($heap->find('user', self::INDEX_FIND_1_2));
        \Testo\Assert::false($heap->has($entity1));
        \Testo\Assert::false($heap->has($entity2));
    }

    #[\Testo\Test]
    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, self::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [self::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [self::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->get($entity1));
        \Testo\Assert::null($heap->get($entity2));
    }

    #[\Testo\Test]
    public function testClean(): void
    {
        $this->createHeap()->clean();

        \Testo\Assert::true(true, 'There are no exceptions');
    }

    protected function createHeap(): HeapInterface
    {
        return new NullHeap();
    }
}
