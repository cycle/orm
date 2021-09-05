<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

abstract class HasManyCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const
        CHILD_CONTAINER = 'children';
    protected const
        PARENT_1 = ['key1' => 1, 'key2' => 1, 'key3' => 101];
    protected const
        PARENT_2 = ['key1' => 1, 'key2' => 2, 'key3' => 102];
    protected const
        PARENT_3 = ['key1' => 2, 'key2' => 1, 'key3' => 201];
    protected const
        PARENT_4 = ['key1' => 2, 'key2' => 2, 'key3' => 202];
    protected const
        CHILD_1_1 = ['key1' => 1, 'key2' => 1, 'key3' => null, 'parent_key1' => 1, 'parent_key2' => 1];
    protected const
        CHILD_1_2 = ['key1' => 1, 'key2' => 2, 'key3' => 'foo1', 'parent_key1' => 1, 'parent_key2' => 1];
    protected const
        CHILD_1_3 = ['key1' => 1, 'key2' => 3, 'key3' => 'bar1', 'parent_key1' => 1, 'parent_key2' => 1];
    protected const
        CHILD_2_1 = ['key1' => 2, 'key2' => 1, 'key3' => 'foo2', 'parent_key1' => 1, 'parent_key2' => 2];
    protected const
        CHILD_3_1 = ['key1' => 3, 'key2' => 1, 'key3' => 'bar3', 'parent_key1' => 2, 'parent_key2' => 1];
    protected const
        PARENT_1_FULL = self::PARENT_1 + [self::CHILD_CONTAINER => [self::CHILD_1_1, self::CHILD_1_2, self::CHILD_1_3]];
    protected const
        PARENT_2_FULL = self::PARENT_2 + [self::CHILD_CONTAINER => [self::CHILD_2_1]];
    protected const
        PARENT_3_FULL = self::PARENT_3 + [self::CHILD_CONTAINER => [self::CHILD_3_1]];
    protected const
        PARENT_4_FULL = self::PARENT_4 + [self::CHILD_CONTAINER => []];
    protected const
        SET_FULL = [
            self::PARENT_1_FULL,
            self::PARENT_2_FULL,
            self::PARENT_3_FULL,
            self::PARENT_4_FULL,
        ];

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'parent_entity',
            [
                'pField1' => 'bigInteger,primary',
                'pField2' => 'bigInteger,primary',
                'pField3' => 'integer,nullable',
            ]
        );
        $this->makeTable(
            'child_entity',
            [
                'field1' => 'bigInteger,primary',
                'field2' => 'bigInteger,primary',
                'field3' => 'string,nullable',
                'parent_field1' => 'bigInteger,null',
                'parent_field2' => 'bigInteger,null',
            ]
        );

        $this->makeCompositeFK(
            'child_entity',
            ['parent_field1', 'parent_field2'],
            'parent_entity',
            ['pField1', 'pField2']
        );

        $this->getDatabase()->table('parent_entity')->insertMultiple(
            ['pField1', 'pField2', 'pField3'],
            [
                self::PARENT_1,
                self::PARENT_2,
                self::PARENT_3,
                self::PARENT_4,
            ]
        );
        $this->getDatabase()->table('child_entity')->insertMultiple(
            ['field1', 'field2', 'field3', 'parent_field1', 'parent_field2'],
            [
                self::CHILD_1_1,
                self::CHILD_1_2,
                self::CHILD_1_3,
                self::CHILD_2_1,
                self::CHILD_3_1,
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testInitRelation(): void
    {
        $u = $this->orm->make(CompositePK::class);
        $this->assertInstanceOf(ArrayCollection::class, $u->children);
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3');

        $this->assertEquals(self::SET_FULL, $selector->fetchData());
    }

    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER, ['method' => JoinableLoader::INLOAD])->orderBy('parent_entity.key3');

        $this->assertEquals(self::SET_FULL, $selector->fetchData());
    }

    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $data = $selector->with(self::CHILD_CONTAINER)->buildQuery()->fetchAll();
        // only 3 parents have children
        $this->assertCount(3, $data[0]);
    }

    public function testAccessRelated(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b, $c, $d] = $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3')->fetchAll();

        $this->assertInstanceOf(Collection::class, $a->children);
        $this->assertInstanceOf(Collection::class, $b->children);

        $this->assertCount(3, $a->children);
        $this->assertCount(1, $b->children);
        $this->assertCount(1, $c->children);
        $this->assertCount(0, $d->children);

        $this->assertSame(self::CHILD_1_1['key3'], $a->children[0]->key3);
        $this->assertSame(self::CHILD_1_2['key3'], $a->children[1]->key3);
        $this->assertSame(self::CHILD_1_3['key3'], $a->children[2]->key3);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b] = $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3')->fetchAll();

        $this->captureWriteQueries();
        (new Transaction($this->orm))
            ->persist($a)
            ->persist($b)
            ->run();
        $this->assertNumWrites(0);
    }

    public function testCreateWithRelations(): void
    {
        $e = new CompositePK();
        $e->key1 = 91;
        $e->key2 = 91;
        $e->children->add(new CompositePKChild());
        $e->children->add(new CompositePKChild());

        $e->children[0]->key1 = 911;
        $e->children[0]->key2 = 912;
        $e->children[0]->key3 = 'bar9';
        $e->children[1]->key1 = 921;
        $e->children[1]->key2 = 922;
        $e->children[1]->key3 = 'bar14';

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($e)->run();
        $this->assertNumWrites(3);

        // consecutive test
        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($e)->run();
        $this->assertNumWrites(0);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($e->children[0]));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->children[0])->getStatus());
        $this->assertSame($e->key1, $this->orm->getHeap()->get($e->children[0])->getData()['parent_key1']);
        $this->assertSame($e->key2, $this->orm->getHeap()->get($e->children[0])->getData()['parent_key2']);

        $this->assertTrue($this->orm->getHeap()->has($e->children[1]));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->children[1])->getStatus());
        $this->assertSame($e->key1, $this->orm->getHeap()->get($e->children[1])->getData()['parent_key1']);
        $this->assertSame($e->key2, $this->orm->getHeap()->get($e->children[1])->getData()['parent_key2']);

        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER);

        $this->assertEquals(
            [
                [
                    'key1' => 91,
                    'key2' => 91,
                    'key3' => null,
                    'children' => [
                        [
                            'key1' => 911,
                            'key2' => 912,
                            'key3' => 'bar9',
                            'parent_key1' => 91,
                            'parent_key2' => 91,
                        ],
                        [
                            'key1' => 921,
                            'key2' => 922,
                            'key3' => 'bar14',
                            'parent_key1' => 91,
                            'parent_key2' => 91,
                        ],
                    ],
                ],
            ],
            $selector->wherePK([91, 91])->fetchData()
        );
    }

    public function testRemoveChildren(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->orderBy('parent_entity.key3')
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        $e->children->remove(1);

        $this->save($e);

        $selector = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->orderBy('parent_entity.key3')
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        $this->assertCount(2, $e->children);

        $this->assertSame(self::CHILD_1_1['key3'], $e->children[0]->key3);
        $this->assertSame(self::CHILD_1_3['key3'], $e->children[1]->key3);
    }

    public function testRemoveChildrenNullable(): void
    {
        $schemaArray = $this->getSchemaArray();
        $relationSchema = &$schemaArray[CompositePK::class][Schema::RELATIONS][self::CHILD_CONTAINER][Relation::SCHEMA];
        $relationSchema[Relation::NULLABLE] = true;
        $this->orm = $this->withSchema(new Schema($schemaArray));

        $selector = (new Select($this->orm, CompositePK::class))
            ->orderBy('parent_entity.key3')
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        $e->children->remove(1);

        (new Transaction($this->orm))->persist($e)->run();

        $selector = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->orderBy('parent_entity.key3')
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        $this->assertCount(2, $e->children);

        $this->assertSame(self::CHILD_1_1['key3'], $e->children[0]->key3);
        $this->assertSame(self::CHILD_1_3['key3'], $e->children[1]->key3);

        $this->assertSame(5, (new Select($this->orm, CompositePKChild::class))->count());
    }

    public function testAddAndRemoveChildren(): void
    {
        /** @var CompositePK $e */
        $e = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([1, 1])->fetchOne();

        $e->children->remove(1);

        $c = new CompositePKChild();
        $c->key1 = 89;
        $c->key2 = 90;
        $c->key3 = 'fiz';
        $e->children->add($c);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(2);

        // consecutive test
        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        $selector = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        $this->assertCount(3, $e->children);

        $this->assertSame(self::CHILD_1_1['key3'], $e->children[0]->key3);
        $this->assertSame(self::CHILD_1_3['key3'], $e->children[1]->key3);
        $this->assertSame('fiz', $e->children[2]->key3);
    }

    public function testSliceAndSaveToAnotherParent(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('parent_entity.key3');
        /**
         * @var CompositePK $a
         * @var CompositePK $b
         * @var CompositePK $c
         * @var CompositePK $d
         */
        [$a, $b, $c, $d] = $selector->fetchAll();

        $this->assertCount(3, $a->children);
        $this->assertCount(0, $d->children);

        $d->children = $a->children->slice(0, 2);
        foreach ($d->children as $c) {
            $a->children->removeElement($c);
        }

        $d->children[0]->key3 = 'new value';

        $this->assertCount(1, $a->children);
        $this->assertCount(2, $d->children);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($a)->persist($d)->run();
        $this->assertNumWrites(2);

        // consecutive
        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($a)->persist($d)->run();
        $this->assertNumWrites(0);

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);

        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b, $c, $d] = $selector->load(self::CHILD_CONTAINER, [
            'method' => JoinableLoader::INLOAD,
            'as' => 'child',
        ])->orderBy('parent_entity.key3')->fetchAll();

        $this->assertCount(1, $a->children);
        $this->assertCount(2, $d->children);

        $this->assertSame(self::CHILD_1_3['key1'], $a->children[0]->key1);
        $this->assertSame(self::CHILD_1_3['key2'], $a->children[0]->key2);
        $this->assertSame(self::CHILD_1_1['key1'], $d->children[0]->key1);
        $this->assertSame(self::CHILD_1_1['key2'], $d->children[0]->key2);
        $this->assertSame(self::CHILD_1_2['key1'], $d->children[1]->key1);
        $this->assertSame(self::CHILD_1_2['key2'], $d->children[1]->key2);

        $this->assertSame('new value', $d->children[0]->key3);
    }

    private function getSchemaArray(): array
    {
        return [
            CompositePK::class => [
                Schema::ROLE => 'parent_entity',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'parent_entity',
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS => [
                    'key1' => 'pField1',
                    'key2' => 'pField2',
                    'key3' => 'pField3',
                ],
                Schema::TYPECAST => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'key3' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['key1', 'key2'],
                            Relation::OUTER_KEY => ['parent_key1', 'parent_key2'],
                            Relation::ORDER_BY => ['key1' => 'asc', 'key2' => 'asc'],
                        ],
                    ],
                ],
            ],
            CompositePKChild::class => [
                Schema::ROLE => 'child_entity',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'child_entity',
                Schema::MAPPER => Mapper::class,
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS => [
                    'key1' => 'field1',
                    'key2' => 'field2',
                    'key3' => 'field3',
                    'parent_key1' => 'parent_field1',
                    'parent_key2' => 'parent_field2',
                ],
                Schema::TYPECAST => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'parent_key1' => 'int',
                    'parent_key2' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ];
    }
}
