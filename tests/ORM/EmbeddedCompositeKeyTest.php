<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class EmbeddedCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const
        CHILD_CONTAINER = 'child_entity',
        CHILD_ROLE = 'parent_entity:' . self::CHILD_CONTAINER,

        KEY_1 = ['key1' => 1, 'key2' => 1],
        KEY_2 = ['key1' => 1, 'key2' => 2],
        KEY_3 = ['key1' => 2, 'key2' => 1],

        PARENT_1 = ['key3' => 1],
        PARENT_2 = ['key3' => 2],
        PARENT_3 = ['key3' => 3],

        CHILD_1 = ['key3' => 'foo'],
        CHILD_2 = ['key3' => 'bar'],
        CHILD_3 = ['key3' => 'baz'],

        ALL_LOADED = [
            self::KEY_1 + self::PARENT_1 + [self::CHILD_CONTAINER => self::KEY_1 + self::CHILD_1],
            self::KEY_2 + self::PARENT_2 + [self::CHILD_CONTAINER => self::KEY_2 + self::CHILD_2],
            self::KEY_3 + self::PARENT_3 + [self::CHILD_CONTAINER => self::KEY_3 + self::CHILD_3],
        ];

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'parent_entity',
            [
                'pField1' => 'bigInteger,primary',
                'pField2' => 'bigInteger,primary',
                'pField3' => 'integer',
                'cField3' => 'string',
            ]
        );

        $this->getDatabase()->table('parent_entity')->insertMultiple(
            ['pField1', 'pField2', 'pField3', 'cField3'],
            [
                array_merge(self::KEY_1, array_values(self::PARENT_1), array_values(self::CHILD_1)),
                array_merge(self::KEY_2, array_values(self::PARENT_2), array_values(self::CHILD_2)),
                array_merge(self::KEY_3, array_values(self::PARENT_3), array_values(self::CHILD_3)),
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    private function getSchemaArray(): array
    {
        return [
            CompositePK::class => [
                Schema::ROLE        => 'parent_entity',
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'parent_entity',
                Schema::MAPPER      => Mapper::class,
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS     => [
                    'key1' => 'pField1',
                    'key2' => 'pField2',
                    'key3' => 'pField3',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'key3' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => self::CHILD_ROLE,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ]
            ],
            CompositePKChild::class => [
                Schema::ROLE        => self::CHILD_ROLE,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'parent_entity',
                Schema::MAPPER      => Mapper::class,
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS     => [
                    'key1'        => 'pField1',
                    'key2'        => 'pField2',
                    'key3'        => 'cField3',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'key2' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
        ];
    }

    public function testFetchData(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        $this->assertEquals(self::ALL_LOADED, $selector->fetchData());
    }

    public function testInitRelation(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        [$a, $b, $c] = $selector->fetchAll();

        $this->assertInstanceOf(CompositePKChild::class, $a->child_entity);
        $this->assertInstanceOf(CompositePKChild::class, $b->child_entity);

        $this->assertSame('foo', $a->child_entity->key3);
        $this->assertSame('bar', $b->child_entity->key3);
        $this->assertSame('baz', $c->child_entity->key3);
    }

    public function testInitRelationFetchOne(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $this->assertInstanceOf(CompositePKChild::class, $u->child_entity);
        $this->assertSame('foo', $u->child_entity->key3);
    }

    public function testCreateParentWithEmbedded(): void
    {
        $u = new CompositePK();
        $u->key1 = 800;
        $u->key2 = 801;
        $u->key3 = 900;
        $u->child_entity = new CompositePKChild();
        $u->child_entity->key3 = 'user3';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = ((new Select($this->orm->withHeap(new Heap()), CompositePK::class)))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        $this->assertSame($u->key1, $u2->key1);
        $this->assertSame($u->key2, $u2->key2);
        $this->assertSame($u->key1, $u2->child_entity->key1);
        $this->assertSame($u->key2, $u2->child_entity->key2);
        $this->assertSame('user3', $u2->child_entity->key3);
    }

    public function testNoWrites(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    public function testUpdateEmbeddedValue(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $u->child_entity->key3 = 'newpass';

        // make sure no other fields are updated
        $this->dbal->database()->table('parent_entity')->update(
            ['pField3' => 808],
            [
                'pField1' => $u->key1,
                'pField2' => $u->key2,
            ]
        )->run();

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        $this->assertEquals($u->key1, $u2->key1);
        $this->assertEquals($u->key2, $u2->key2);
        $this->assertEquals(808, $u2->key3);
        $this->assertSame('newpass', $u2->child_entity->key3);
    }

    public function testInitRelationReferenceNothing(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    public function testUpdateEmbeddedDirectly(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->load(self::CHILD_CONTAINER)
            ->fetchOne();

        $this->captureWriteQueries();
        $this->save($u->child_entity);
        $this->assertNumWrites(0);

        $u->child_entity->key3 = 'altered';

        $this->captureWriteQueries();
        $this->save($u->child_entity);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])->fetchOne();

        $this->assertEquals($u->key1, $u2->key1);
        $this->assertEquals($u->key2, $u2->key2);
        $this->assertSame('altered', $u2->child_entity->key3);
    }

    public function testResolvePromise(): void
    {
        /** @var CompositePK $u */
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $this->assertSame('foo', $u->child_entity->key3);
    }

    public function testChangePromise(): void
    {
        /** @var CompositePK $u */
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $u->child_entity->key3 = 'user3';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        $this->assertEquals($u->key1, $u2->key1);
        $this->assertEquals($u->key2, $u2->key2);
        $this->assertSame('user3', $u2->child_entity->key3);
    }

    public function testSavePromise(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $u = $selector->orderBy('key3', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $this->save($u);
        $this->assertNumWrites(0);
        $this->assertNumReads(0);
    }

    public function testMovePromise(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $u2 = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->wherePK(array_values(self::KEY_2))
            ->fetchOne();

        $u2Data = $this->extractEntity($u2);

        $u->child_entity = $u2Data['child_entity'];

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $this->save($u);
        $this->assertNumWrites(1);
        $this->assertNumReads(1);

        $u3 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        $this->assertEquals($u->key1, $u3->key1);
        $this->assertEquals($u->key2, $u3->key2);
        $this->assertSame('bar', $u3->child_entity->key3);

        $u4 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u2->key1, $u2->key2])
            ->fetchOne();

        // unchanged
        $this->assertEquals($u2->key1, $u4->key1);
        $this->assertEquals($u2->key2, $u4->key2);
        $this->assertSame('bar', $u4->child_entity->key3);
    }

    public function testMoveClonedEmbedding(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $u2 = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->wherePK(array_values(self::KEY_2))
            ->fetchOne();

        $u->child_entity = clone $u2->child_entity;

        $this->captureWriteQueries();
        $this->captureReadQueries();
        $this->save($u);
        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $u3 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        $this->assertSame($u->key1, $u3->key1);
        $this->assertSame($u->key2, $u3->key2);
        $this->assertSame('bar', $u3->child_entity->key3);

        $u4 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u2->key1, $u2->key2])
            ->fetchOne();

        // unchanged
        $this->assertSame($u2->key1, $u4->key1);
        $this->assertSame($u2->key2, $u4->key2);
        $this->assertSame('bar', $u4->child_entity->key3);
    }

    public function testSelectEmbeddable(): void
    {
        $u = (new Select($this->orm, CompositePKChild::class))
            ->orderBy('key1', 'ASC')
            ->orderBy('key2', 'ASC')
            ->fetchOne();

        $this->assertSame('foo', $u->key3);
        $this->assertSame(array_values(self::KEY_1), [$u->key1, $u->key2]);
    }

    public function testChangeWhole(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        $u->child_entity = new CompositePKChild();
        $u->child_entity->key3 = 'abc';

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)->wherePK([$u->key1, $u->key2])->fetchOne();

        $this->assertSame($u->key1, $u2->key1);
        $this->assertSame($u->key2, $u2->key2);
        $this->assertSame('abc', $u2->child_entity->key3);
    }

    public function testNullify(): void
    {
        $this->expectException(NullException::class);

        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')->fetchOne();

        $u->child_entity = null;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)->wherePK([$u->key1, $u->key2])->fetchOne();

        $this->assertSame($u->key1, $u2->key1);
        $this->assertSame($u->key2, $u2->key2);
        $this->assertSame('user3', $u2->child_entity->key3);
    }
}
