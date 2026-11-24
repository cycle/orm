<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Heap;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Tests\Fixtures\User;
use Testo\Assert;
use Testo\Test;

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

    #[Test]
    public function testAttachAndFind(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity2, 'Found');
        Assert::null($heap->find('user', static::INDEX_FIND_1_BAD), 'Not found');
        Assert::null($heap->find('user', []), 'Empty scope');
    }

    #[Test]
    public function testIndexAsArray(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [(array) static::INDEX_FIELDS_1]);

        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity, 'Found');
    }

    #[Test]
    public function testFindByNotExistingIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        Assert::null($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    #[Test]
    public function testAttachWithEmptyIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [[], static::INDEX_FIELDS_1]);

        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity, 'Found');
        Assert::null($heap->find('user', static::INDEX_FIND_2_1), 'Index not found');
    }

    #[Test]
    public function testAttachWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_BAD, static::INDEX_FIELDS_2]);

        Assert::null($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity, 'Found');

        // No error
        $heap->detach($entity);
    }

    #[Test]
    public function testFindWithBadIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        Assert::null($heap->find('user', static::INDEX_FIND_BAD), 'Bad index');
        Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity, 'Found');
    }

    #[Test]
    public function testFindBadRole(): void
    {
        $heap = $this->createHeap();

        Assert::null($heap->find('bad_role', static::INDEX_FIND_BAD));
    }

    #[Test]
    public function testFindEmptyCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        Assert::null($heap->find('user', []));
    }

    #[Test]
    public function testDetach(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::INDEX_FIND_1_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity1, 'Found');
        Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity2, 'Found');
        Assert::true($heap->has($entity1));
        Assert::true($heap->has($entity2));

        // Now detach it
        $heap->detach($entity1);
        $heap->detach($entity2);

        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        Assert::null($heap->find('user', static::INDEX_FIND_1_2));
        Assert::false($heap->has($entity1));
        Assert::false($heap->has($entity2));
    }

    #[Test]
    public function testNotEntity(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::INDEX_FIND_1_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        Assert::true($heap->has($entity));

        $heap->detach(new \stdClass());

        Assert::true($heap->has($entity));
    }

    #[Test]
    public function testGet(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, ['email' => 'test1'], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        Assert::same($heap->get($entity1), $node1);
        Assert::same($heap->get($entity2), $node2);
    }

    #[Test]
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

        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        Assert::false($heap->has($entity1));
        Assert::false($heap->has($entity2));

        $count = 0;
        foreach ($heap as $value) {
            ++$count;
        }
        Assert::same($count, 0);
    }

    #[Test]
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

        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity);
    }

    #[Test]
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

        Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity1);
        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);
    }

    #[Test]
    public function testOverwriteEntity(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity1 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);

        $node2 = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity2 = new User();
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);

        $heap->detach($entity1);

        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity2);
    }

    #[Test]
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
        Assert::same($heap->find('user', static::INDEX_FIND_1_2), $entity1);
        // old value has been detached
        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[Test]
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

        Assert::true($heap->has($entity));
        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[Test]
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

        Assert::false($heap->has($entity));
        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
    }

    #[Test]
    public function testEmptyNode(): void
    {
        $heap = $this->createHeap();
        $node1 = new Node(Node::NEW, [], 'user');
        $entity1 = new User();
        $node2 = new Node(Node::NEW, static::ENTITY_SET_2, 'user');
        $entity2 = new User();
        $heap->attach($entity1, $node1, [static::INDEX_FIELDS_1]);
        $heap->attach($entity2, $node2, [static::INDEX_FIELDS_1]);

        Assert::true($heap->has($entity2));
        Assert::true($heap->has($entity1));

        $heap->detach($entity2);
        $heap->detach($entity1);

        Assert::false($heap->has($entity2));
        Assert::false($heap->has($entity1));
    }

    #[Test]
    public function testAttachWithNewIndex(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, static::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [static::INDEX_FIELDS_1]);

        Assert::null($heap->find('user', static::INDEX_FIND_2_1));

        $heap->attach($entity, $node, [static::INDEX_FIELDS_2]);

        Assert::same($heap->find('user', static::INDEX_FIND_2_1), $entity);
        // old index was not deleted
        Assert::same($heap->find('user', static::INDEX_FIND_1_1), $entity);
    }

    #[Test]
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

        Assert::null($heap->find('user', static::INDEX_FIND_1_1));
        Assert::null($heap->find('user', static::INDEX_FIND_1_2));
        Assert::null($heap->find('user', static::INDEX_FIND_2_1));
        Assert::same($heap->find('user', static::INDEX_FIND_2_2), $entity);
    }

    protected function createHeap(): HeapInterface
    {
        return new Heap();
    }
}
