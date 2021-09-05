<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Heap;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\User;
use JetBrains\PhpStorm\ArrayShape;

final class HeapCompositeKeysTest extends HeapTest
{
    protected const
        INDEX_FIELDS_1 = ['id', 'user_code'];
    protected const
        INDEX_VALUES_1_1 = [42, 'ytrewq'];
    protected const
        INDEX_VALUES_1_2 = [24, 'qwerty'];
    protected const
        INDEX_FIND_1_1 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_1[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_1[1],
        ];
    protected const
        INDEX_FIND_1_2 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_2[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_2[1],
        ];
    protected const
        INDEX_FIND_1_BAD = [
            self::INDEX_FIELDS_1[0] => 404,
            self::INDEX_FIELDS_1[1] => 'none',
        ];
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
        INDEX_FIELDS_BAD = [self::INDEX_FIELDS_1[0], self::INDEX_FIELDS_1[1], 'foo'];
    protected const
        INDEX_FIND_BAD = self::INDEX_FIND_1_1 + [self::INDEX_FIELDS_BAD[2] => null];
    protected const
        ENTITY_SET_1 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_1[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_1[1],
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_1,
        ];
    protected const
        ENTITY_SET_2 = [
            self::INDEX_FIELDS_1[0] => self::INDEX_VALUES_1_2[0],
            self::INDEX_FIELDS_1[1] => self::INDEX_VALUES_1_2[1],
            self::INDEX_FIELDS_2 => self::INDEX_VALUES_2_2,
        ];

    public function testFindByShuffledCriteria(): void
    {
        $heap = $this->createHeap();
        $node = new Node(Node::NEW, self::ENTITY_SET_1, 'user');
        $entity = new User();
        $heap->attach($entity, $node, [self::INDEX_FIELDS_1]);

        $this->assertSame($entity, $heap->find('user', array_reverse(self::INDEX_FIND_1_1, true)), 'Found');
    }

    public function testDetachSomeFromMultipleWithSamePartPK(): void
    {
        [
            'heap' => $heap,
            'values' => $values,
            'entities' => $entities,
            'nodes' => $nodes,
        ] = $this->prepareHeapWithMultipleValues();

        // Now detach every second
        foreach ($values as $i => $value) {
            if ($i % 2 === 0) {
                continue;
            }
            $heap->detach($entities[$i]);
        }

        // Check
        foreach ($values as $i => $value) {
            if ($i % 2 === 0) {
                $this->assertTrue($heap->has($entities[$i]));
                $this->assertSame($entities[$i], $heap->find('user', $value), "Item {$i} found.");
            } else {
                $this->assertFalse($heap->has($entities[$i]));
                $this->assertNull($heap->find('user', $value), "Item {$i} removed.");
            }
        }
    }

    public function testDetachMultipleWithSamePartPK(): void
    {
        [
            'heap' => $heap,
            'values' => $values,
            'entities' => $entities,
            'nodes' => $nodes,
        ] = $this->prepareHeapWithMultipleValues();

        // Now detach it
        foreach ($values as $i => $value) {
            $this->assertTrue($heap->has($entities[$i]));
            $this->assertSame($entities[$i], $heap->find('user', $value), "Item {$i} exists.");

            $heap->detach($entities[$i]);

            $this->assertFalse($heap->has($entities[$i]));
            $this->assertNull($heap->find('user', $value), "Item {$i} removed.");
        }
    }

    #[ArrayShape(['heap' => HeapInterface::class, 'values' => 'array', 'entities' => 'array', 'nodes' => 'array'])]
    private function prepareHeapWithMultipleValues(): array
    {
        $heap = $this->createHeap();
        $values = [
            ['key1' => 10, 'key2' => 20, 'key3' => 30],
            ['key1' => 10, 'key2' => 20, 'key3' => 31],
            ['key1' => 10, 'key2' => 20, 'key3' => 32],
            ['key1' => 10, 'key2' => 21, 'key3' => 30],
            ['key1' => 10, 'key2' => 21, 'key3' => 31],
            ['key1' => 10, 'key2' => 21, 'key3' => 32],
            ['key1' => 11, 'key2' => 20, 'key3' => 30],
            ['key1' => 11, 'key2' => 20, 'key3' => 31],
            ['key1' => 11, 'key2' => 20, 'key3' => 32],
            ['key1' => 11, 'key2' => 21, 'key3' => 30],
            ['key1' => 11, 'key2' => 21, 'key3' => 31],
            ['key1' => 11, 'key2' => 21, 'key3' => 32],
        ];

        foreach ($values as $i => $value) {
            $node = new Node(Node::NEW, $value, 'user');
            $entity = new CompositePK();
            $heap->attach($entity, $node, [['key1', 'key2', 'key3']]);
            $entities[$i] = $entity;
            $nodes[$i] = $node;
        }

        // Check
        foreach ($values as $i => $value) {
            $this->assertTrue($heap->has($entities[$i]));
            $this->assertSame($entities[$i], $heap->find('user', $value), "Item {$i} found.");
        }

        return ['heap' => $heap, 'values' => $values, 'entities' => $entities, 'nodes' => $nodes];
    }
}
