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
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Fixtures\CompositePKNested;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Spiral\Database\Injection\Parameter;

abstract class HasOneCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const
        CHILD_CONTAINER = 'child_entity',
        NESTED_CONTAINER = 'nested',

        PARENT_1 = ['key1' => 1, 'key2' => 1, 'key3' => 101],
        PARENT_2 = ['key1' => 1, 'key2' => 2, 'key3' => 102],
        PARENT_3 = ['key1' => 2, 'key2' => 1, 'key3' => 201],
        PARENT_4 = ['key1' => 2, 'key2' => 2, 'key3' => 202],

        CHILD_1 = ['key1' => 1, 'key2' => 1, 'key3' => null, 'parent_key1' => 1, 'parent_key2' => 1],
        CHILD_2 = ['key1' => 1, 'key2' => 2, 'key3' => 'foo2', 'parent_key1' => 1, 'parent_key2' => 2],
        CHILD_3 = ['key1' => 1, 'key2' => 3, 'key3' => 'bar3', 'parent_key1' => 2, 'parent_key2' => 1],

        NESTED_1 = ['key3' => 'foo', 'parent_key1' => 1, 'parent_key2' => 1],

        PARENT_1_LOADED = self::PARENT_1 + [self::CHILD_CONTAINER => self::CHILD_1],
        PARENT_2_LOADED = self::PARENT_2 + [self::CHILD_CONTAINER => self::CHILD_2],
        PARENT_3_LOADED = self::PARENT_3 + [self::CHILD_CONTAINER => self::CHILD_3],
        PARENT_4_LOADED = self::PARENT_4 + [self::CHILD_CONTAINER => null],
        PARENT_1_NESTED = self::PARENT_1 + [self::CHILD_CONTAINER => self::CHILD_1 + [
            self::NESTED_CONTAINER => self::NESTED_1 + ['key1' => 1]
        ]],
        PARENT_2_NESTED = self::PARENT_2 + [self::CHILD_CONTAINER => self::CHILD_2 + [self::NESTED_CONTAINER => null]],
        PARENT_3_NESTED = self::PARENT_3 + [self::CHILD_CONTAINER => self::CHILD_3 + [self::NESTED_CONTAINER => null]],
        PARENT_4_NESTED = self::PARENT_4 + [self::CHILD_CONTAINER => null],

        FULL_LOADED = [
            self::PARENT_1_LOADED,
            self::PARENT_2_LOADED,
            self::PARENT_3_LOADED,
            self::PARENT_4_LOADED,
        ],
        FULL_NESTED = [
            self::PARENT_1_NESTED,
            self::PARENT_2_NESTED,
            self::PARENT_3_NESTED,
            self::PARENT_4_NESTED,
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
        $this->makeTable(
            'nested_entity',
            [
                'field1' => 'primary',
                'field3' => 'string,null',
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
        $this->makeCompositeFK(
            'nested_entity',
            ['parent_field1', 'parent_field2'],
            'child_entity',
            ['field1', 'field2']
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
                self::CHILD_1,
                self::CHILD_2,
                self::CHILD_3,
            ]
        );
        $this->getDatabase()->table('nested_entity')->insertMultiple(
            ['field3', 'parent_field1', 'parent_field2'],
            [
                self::NESTED_1,
            ]
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testHasInSchema(): void
    {
        $this->assertSame(['child_entity'], $this->orm->getSchema()->getRelations('parent_entity'));
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load('child_entity');

        $this->assertSame(self::FULL_LOADED, $selector->fetchData());
    }

    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $data = $selector->with('child_entity')->buildQuery()->fetchAll();

        $this->assertSame(3, count($data[0]));
    }

    public function testFetchRelationPostload(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load('child_entity', ['method' => JoinableLoader::POSTLOAD]);

        $this->assertSame(self::FULL_LOADED, $selector->fetchData());
    }

    public function testAccessEntities(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load('child_entity');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(CompositePK::class, $result[0]);
        $this->assertInstanceOf(CompositePKChild::class, $result[0]->child_entity);
        $this->assertSame(1, $result[0]->child_entity->key1);
        $this->assertSame(1, $result[0]->child_entity->key2);

        $this->assertInstanceOf(CompositePK::class, $result[3]);
        $this->assertEquals(null, $result[3]->child_entity);
    }

    public function testCreateWithRelations(): void
    {
        $e = new CompositePK();
        $e->key1 = 9;
        $e->key2 = 9;
        $e->key3 = 909;
        $e->child_entity = new CompositePKChild();
        $e->child_entity->key1 = 15;
        $e->child_entity->key2 = 25;

        (new Transaction($this->orm))
            ->persist($e)
            ->run();

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($e->child_entity));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->child_entity)->getStatus());

        $this->assertSame($e->key1, $this->orm->getHeap()->get($e->child_entity)->getData()['parent_key1']);
        $this->assertSame($e->key2, $this->orm->getHeap()->get($e->child_entity)->getData()['parent_key2']);
    }

    public function testMountRelation(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->where(['key1' => 2, 'key2' => 2])->fetchOne();

        $e->child_entity = new CompositePKChild();
        $e->child_entity->key1 = 88;
        $e->child_entity->key2 = 99;
        $e->child_entity->key3 = 'foo';

        (new Transaction($this->orm))
            ->persist($e)
            ->run();

        $selector = (new Select($this->orm, CompositePK::class))->where(['key1' => 2, 'key2' => 2]);
        $selector->load('child_entity');

        $this->assertEquals([
            [
                'key1' => 2,
                'key2' => 2,
                'key3' => 202,
                'child_entity' => [
                    'key1' => 88,
                    'key2' => 99,
                    'key3' => 'foo',
                    'parent_key1' => 2,
                    'parent_key2' => 2,
                ],
            ],
        ], $selector->fetchData());
    }

    # todo test changing child PK
    public function testCreateAndUpdateRelatedData(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->where(['key1' => 2, 'key2' => 2])->fetchOne();

        $e->child_entity = new CompositePKChild();
        $e->child_entity->key1 = 88;
        $e->child_entity->key2 = 99;
        $e->child_entity->key3 = 'foo';

        (new Transaction($this->orm))
            ->persist($e)
            ->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Select($orm, CompositePK::class);
        $e = $selector->wherePK([2, 2])->load('child_entity')->fetchOne();

        $this->assertSame('foo', $e->child_entity->key3);

        $e->child_entity->key3 = 'bar';

        $this->captureWriteQueries();
        (new Transaction($orm))
            ->persist($e)
            ->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        (new Transaction($orm))
            ->persist($e)
            ->run();
        $this->assertNumWrites(0);

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Select($orm, CompositePK::class);
        $e = $selector->wherePK([2, 2])->load('child_entity')->fetchOne();

        $this->assertSame('bar', $e->child_entity->key3);
    }

    public function testDeleteChildrenByAssigningNull(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity')->fetchOne();
        $e->child_entity = null;
        $this->assertSame(3, (new Select($this->orm, CompositePKChild::class))->count());

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity')->fetchOne();

        $this->assertSame(null, $e->child_entity);
        $this->assertSame(2, (new Select($this->orm, CompositePKChild::class))->count());
    }

    public function testAssignNewChild(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity')->fetchOne();

        $oP = $e->child_entity;
        $e->child_entity = new CompositePKChild();
        $e->child_entity->key1 = 100;
        $e->child_entity->key2 = 200;
        $e->child_entity->key3 = 'foo';

        (new Transaction($this->orm))
            ->persist($e)
            ->run();

        $this->assertFalse($this->orm->getHeap()->has($oP));
        $this->assertTrue($this->orm->getHeap()->has($e->child_entity));

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity')->fetchOne();

        $this->assertNotEquals($oP, $e->child_entity);
        $this->assertSame('foo', $e->child_entity->key3);
    }

    public function testDeleteNullableChild(): void
    {
        $schemaArray = $this->getSchemaArray();
        $relationSchema = &$schemaArray[CompositePK::class][Schema::RELATIONS]['child_entity'][Relation::SCHEMA];
        $relationSchema[Relation::NULLABLE] = true;

        $this->orm = $this->withSchema(new Schema($schemaArray));

        $e = (new Select($this->orm, CompositePK::class))
            ->wherePK([1, 1])
            ->load('child_entity')
            ->fetchOne();
        $e->child_entity = null;

        (new Transaction($this->orm))->persist($e)->run();

        $e = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->wherePK([1, 1])
            ->load('child_entity')
            ->fetchOne();

        $this->assertSame(null, $e->child_entity);
        $this->assertSame(3, (new Select($this->orm, CompositePKChild::class))->count());
    }

    public function testMoveToAnotherEntity(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        [$a, $b] = $selector->load('child_entity')
            ->where('parent_entity.key3', '>', 200)
            ->orderBy('parent_entity.key3')
            ->fetchAll();

        $this->assertNotNull($a->child_entity);
        $this->assertNull($b->child_entity);

        $compareChild = $a->child_entity;
        [$b->child_entity, $a->child_entity] = [$a->child_entity, null];

        (new Transaction($this->orm))
            ->persist($a)
            ->persist($b)
            ->run();

        $this->assertTrue($this->orm->getHeap()->has($b->child_entity));

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        [$a, $b] = $selector->load('child_entity')
            ->where('parent_entity.key3', '>', 200)
            ->orderBy('parent_entity.key3')
            ->fetchAll();

        $this->assertNull($a->child_entity);
        $this->assertNotNull($b->child_entity);
        $this->assertEquals(
            [$compareChild->key1, $compareChild->key2],
            [$b->child_entity->key1, $b->child_entity->key2]
        );
    }

    public function testExchange(): void
    {
        [$a, $b, $c, $d] = (new Select($this->orm, CompositePK::class))
            ->load('child_entity')
            ->orderBy('parent_entity.key3')
            ->fetchAll();
        $this->assertSame(self::CHILD_1['key3'], $a->child_entity->key3);
        $this->assertSame(self::CHILD_2['key3'], $b->child_entity->key3);

        [$a->child_entity, $b->child_entity] = [$b->child_entity, $a->child_entity];

        (new Transaction($this->orm))->persist($a)->persist($b)->run();

        // reset state
        $this->orm = $this->orm->withHeap(new Heap());

        [$a, $b] = (new Select($this->orm, CompositePK::class))
            ->load('child_entity')
            ->orderBy('parent_entity.key3')
            ->fetchAll();
        $this->assertSame(self::CHILD_2['key3'], $a->child_entity->key3);
        $this->assertSame(self::CHILD_1['key3'], $b->child_entity->key3);
    }

    public function testFetchNestedRelation(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load('child_entity.nested');

        $this->assertEquals(self::FULL_NESTED, $selector->fetchData());
    }

    public function testFetchNestedRelationPostload(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load('child_entity', ['method' => JoinableLoader::POSTLOAD]);
        $selector->load('child_entity.nested');

        $this->assertEquals(self::FULL_NESTED, $selector->fetchData());
    }

    public function testUpdateNestedChild(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity.nested')->fetchOne();

        $e->child_entity->nested->key3 = 'new-label';

        (new Transaction($this->orm))->persist($e)->run();

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity.nested')->fetchOne();

        $this->assertSame('new-label', $e->child_entity->nested->key3);
    }

    public function testChangeNestedChild(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $e = $selector->wherePK([1, 1])->load('child_entity.nested')->fetchOne();

        $e->child_entity->nested = new CompositePKNested();
        $e->child_entity->nested->key3 = 'another';

        (new Transaction($this->orm))->persist($e)->run();

        $e = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->wherePK([1, 1])
            ->load('child_entity.nested')->fetchOne();

        $this->assertSame('another', $e->child_entity->nested->key3);
    }

    public function testNoWriteQueries(): void
    {
        $e = (new Select($this->orm, CompositePK::class))
            ->wherePK([1, 1])
            ->load('child_entity.nested')
            ->fetchOne();

        $e->child_entity->nested = new CompositePKNested();

        (new Transaction($this->orm))->persist($e)->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $e = (new Select($this->orm, CompositePK::class))
            ->wherePK([1, 1])
            ->load('child_entity.nested')
            ->fetchOne();

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($e)->run();
        $this->assertNumWrites(0);
    }

    public function testFindByRelatedID(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->with('child_entity')
            ->where([
                'child_entity.key1' => self::CHILD_1['key1'],
                'child_entity.key2' => self::CHILD_1['key2'],
            ]);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CompositePK::class, $result[0]);
        $this->assertEquals([self::PARENT_1], $selector->fetchData());
    }

    public function testFindByRelatedIDAliased(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->with('child_entity', ['as' => 'child_entity_relation'])
            ->where([
                'child_entity.key1' => self::CHILD_1['key1'],
                'child_entity.key2' => self::CHILD_1['key2'],
            ]);


        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CompositePK::class, $result[0]);
        $this->assertEquals([self::PARENT_1], $selector->fetchData());
    }

    public function testFindByRelatedIDArray(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->with('child_entity')
            ->where([
                'child_entity.key1' => new Parameter([self::CHILD_1['key1']]),
                'child_entity.key2' => new Parameter([self::CHILD_1['key2']]),
            ]);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CompositePK::class, $result[0]);
        $this->assertEquals([self::PARENT_1], $selector->fetchData());
    }

    public function testFindByRelatedColumn(): void
    {
        $selector = new Select($this->orm, CompositePK::class);

        $selector->with('child_entity')->where('child_entity.key3', '=', null);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(CompositePK::class, $result[0]);
        $this->assertEquals([self::PARENT_1], $selector->fetchData());
    }

    public function testDoNotOverwriteRelation(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load('child_entity')
            ->wherePK([1, 1])
            ->fetchOne();

        $newCompositePKChild = new CompositePKChild();
        $newCompositePKChild->key1 = 1;
        $newCompositePKChild->key2 = 1;
        $newCompositePKChild->key3 = 'new';
        $u->child_entity = $newCompositePKChild;

        $u2 = $this->orm
            ->getRepository(CompositePK::class)
            ->findByPK([1, 1]);
        $this->assertSame($u2, $u);
        $this->assertSame('new', $u2->child_entity->key3);

        $u3 = $this->orm->withHeap(new Heap())
            ->getRepository(CompositePK::class)
            ->select()
            ->load('child_entity')
            ->wherePK([1, 1])
            ->fetchOne();

        $this->assertSame(self::CHILD_1['key3'], $u3->child_entity->key3);

        (new Transaction($this->orm))->persist($u)->run();

        $u4 = $this->orm->withHeap(new Heap())->getRepository(CompositePK::class)
                        ->select()->load('child_entity')->wherePK([1, 1])->fetchOne();

        $this->assertSame('new', $u4->child_entity->key3);
    }

    public function testOverwritePromisedRelation(): void
    {
        $u = (new Select($this->orm, CompositePK::class))->wherePK([1, 1])->fetchOne();

        $newCompositePKChild = new CompositePKChild();
        $newCompositePKChild->key1 = 8;
        $newCompositePKChild->key2 = 8;
        $newCompositePKChild->key3 = 'new';
        $u->child_entity = $newCompositePKChild;

        // relation is already set prior to loading
        $u2 = $this->orm->getRepository(CompositePK::class)
            ->select()
            ->load('child_entity')
            ->wherePK([1, 1])->fetchOne();

        $this->assertSame($u, $u2);
        // Overwritten
        $this->assertSame(self::CHILD_1['key3'], $u2->child_entity->key3);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($u)->run();
        $this->assertNumWrites(0);
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
                Schema::RELATIONS   => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => ['key1', 'key2'],
                            Relation::OUTER_KEY => ['parent_key1', 'parent_key2'],
                        ],
                    ]
                ]
            ],
            CompositePKChild::class => [
                Schema::ROLE        => 'child_entity',
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'child_entity',
                Schema::MAPPER      => Mapper::class,
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS     => [
                    'key1'        => 'field1',
                    'key2'        => 'field2',
                    'key3'        => 'field3',
                    'parent_key1' => 'parent_field1',
                    'parent_key2' => 'parent_field2',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'parent_key1' => 'int',
                    'parent_key2' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    self::NESTED_CONTAINER => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => CompositePKNested::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => ['key1', 'key2'],
                            Relation::OUTER_KEY => ['parent_key1', 'parent_key2'],
                        ],
                    ]
                ]
            ],
            CompositePKNested::class => [
                Schema::ROLE        => 'nested_entity',
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested_entity',
                Schema::MAPPER      => Mapper::class,
                Schema::PRIMARY_KEY => ['key1'],
                Schema::COLUMNS     => [
                    'key1'        => 'field1',
                    'key3'        => 'field3',
                    'parent_key1' => 'parent_field1',
                    'parent_key2' => 'parent_field2',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'parent_key1' => 'int',
                    'parent_key2' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
        ];
    }
}
