<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Tests\Fixtures\User;
use PHPUnit\Framework\TestCase;

class HeapTest
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
    protected const INDEX_FIND_2_2 = [self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2];
    protected const INDEX_FIND_2_BAD = [self::INDEX_FIELDS_2 => 505];
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
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity2, 'Found');
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_BAD), 'Not found');
        \Testo\Assert::null($heap->find('user', []), 'Empty scope');
    }

    #[\Testo\Test]
    public function testIndexAsArray(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [(array) static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity, 'Found');
    }

    #[\Testo\Test]
    public function testFindByNotExistingIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    #[\Testo\Test]
    public function testAttachWithEmptyIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [[], static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity, 'Found');
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    #[\Testo\Test]
    public function testAttachWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_BAD, static::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity, 'Found');

        // No error
        $heap->detach($entity);
    }

    #[\Testo\Test]
    public function testFindWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity, 'Found');
    }

    #[\Testo\Test]
    public function testFindBadRole(): void
    {
        $heap = $this->createHeap();

        \Testo\Assert::null($heap->find('bad_role', static::INDEX_FIND_BAD));
    }

    #[\Testo\Test]
    public function testFindEmptyCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', []));
    }

    #[\Testo\Test]
    public function testDetach(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::INDEX_FIND_1_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity1, 'Found');
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity2, 'Found');
        \Testo\Assert::true($heap->has($entity1));
        \Testo\Assert::true($heap->has($entity2));

        // Now detach it
        $heap->detach($entity1);
        $heap->detach($entity2);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_2));
        \Testo\Assert::false($heap->has($entity1));
        \Testo\Assert::false($heap->has($entity2));
    }

    #[\Testo\Test]
    public function testNotEntity(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        \Testo\Assert::true($heap->has($entity));

        $heap->detach(new \stdClass());

        \Testo\Assert::true($heap->has($entity));
    }

    #[\Testo\Test]
    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->get($entity1), $node1);
        \Testo\Assert::same($heap->get($entity2), $node2);
    }

    #[\Testo\Test]
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

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        \Testo\Assert::false($heap->has($entity1));
        \Testo\Assert::false($heap->has($entity2));

        $count = 0;
        foreach ($heap as $value) {
            ++$count;
        }
        \Testo\Assert::same($count, 0);
    }

    #[\Testo\Test]
    public function testSyncWhenIndexedValueChanged(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $state = $node->createState();
        foreach (static::ENTITY_SET_2 as $key => $value) {
            $state->register($key, $value);
        }
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity);
    }

    #[\Testo\Test]
    public function testSyncWhenEntitiesPKSwitch(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $state1 = $node1->createState();
        foreach (static::ENTITY_SET_2 as $key => $value) {
            $state1->register($key, $value);
        }
        $state2 = $node2->createState();
        foreach (static::ENTITY_SET_1 as $key => $value) {
            $state2->register($key, $value);
        }
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity1);
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);
    }

    #[\Testo\Test]
    public function testOverwriteEntity(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);

        $node2 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);

        $heap->detach($entity1);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);
    }

    #[\Testo\Test]
    public function testEditNodeStateWithConflict(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        $node1->setState($state1 = $node1->createState());
        $state1->setData(static::ENTITY_SET_2);
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);

        // entity2 has been overwritten
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity1);
        // old value has been detached
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[\Testo\Test]
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

        $node->setState($state = $node->createState());
        $state->setData($values);
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        \Testo\Assert::true($heap->has($entity));
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[\Testo\Test]
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
        $node->setState($state = $node->createState());
        $state->setData($values);
        $heap->detach($entity);

        \Testo\Assert::false($heap->has($entity));
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[\Testo\Test]
    public function testEmptyNode(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, [], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        \Testo\Assert::true($heap->has($entity2));
        \Testo\Assert::true($heap->has($entity1));

        $heap->detach($entity2);
        $heap->detach($entity1);

        \Testo\Assert::false($heap->has($entity2));
        \Testo\Assert::false($heap->has($entity1));
    }

    #[\Testo\Test]
    public function testAttachWithNewIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_2_1));

        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity);
        // old index was not deleted
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity);
    }

    #[\Testo\Test]
    public function testSyncWhenIndexAndValuesChanged(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        $states = [];
        foreach (static::ENTITY_SET_2 as $key => $value) {
            $state = $node->createState();
            $state->register($key, $value);
            $states[] = $state;
        }
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_1_2));
        \Testo\Assert::null($heap->find('user', static::INDEX_FIND_2_1));
        \Testo\Assert::same($heap->find('user', static::INDEX_FIND_2_2), $entity);
    }

    protected function createHeap(): HeapInterface
    {
        return new Heap();
    }
}
