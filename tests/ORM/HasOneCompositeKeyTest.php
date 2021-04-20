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
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Spiral\Database\Injection\Parameter;

abstract class HasOneCompositeKeyTest extends BaseTest
{
    use TableTrait;

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
                'parent_field1' => 'bigInteger',
                'parent_field2' => 'bigInteger',
            ]
        );
        $this->makeIndex('child_entity', ['parent_field1', 'parent_field2'], false);

        $this->makeCompositeFK('child_entity', ['parent_field1', 'parent_field2'], 'parent_entity', ['pField1', 'pField2']);

        $this->getDatabase()->table('parent_entity')->insertMultiple(
            ['pField1', 'pField2', 'pField3'],
            [
                [1, 1, 101],
                [1, 2, 102],
                [2, 1, 201],
                [2, 2, 202],
            ]
        );
        $this->getDatabase()->table('child_entity')->insertMultiple(
            ['field1', 'field2', 'parent_field1', 'parent_field2'],
            [
                [1, 1, 1, 1],
                [1, 2, 1, 2],
                [1, 3, 2, 1],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
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
                    'child_entity' => [
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
                Schema::RELATIONS   => []
            ]
        ]));

        $this->logger->display();
    }

    public function testHasInSchema(): void
    {
        $this->assertSame(['child_entity'], $this->orm->getSchema()->getRelations('parent_entity'));
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load('child_entity');

        $this->assertSame(
            [
                [
                    'key1' => 1,
                    'key2' => 1,
                    'key3' => 101,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 1,
                        'key3' => null,
                        'parent_key1' => 1,
                        'parent_key2' => 1,
                    ],
                ],
                [
                    'key1' => 1,
                    'key2' => 2,
                    'key3' => 102,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 2,
                        'key3' => null,
                        'parent_key1' => 1,
                        'parent_key2' => 2,
                    ],
                ],
                [
                    'key1' => 2,
                    'key2' => 1,
                    'key3' => 201,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 3,
                        'key3' => null,
                        'parent_key1' => 2,
                        'parent_key2' => 1,
                    ],
                ],
                [
                    'key1' => 2,
                    'key2' => 2,
                    'key3' => 202,
                    'child_entity' => null,
                ],
            ],
            $selector->fetchData()
        );
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

        $this->assertSame(
            [
                [
                    'key1' => 1,
                    'key2' => 1,
                    'key3' => 101,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 1,
                        'key3' => null,
                        'parent_key1' => 1,
                        'parent_key2' => 1,
                    ],
                ],
                [
                    'key1' => 1,
                    'key2' => 2,
                    'key3' => 102,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 2,
                        'key3' => null,
                        'parent_key1' => 1,
                        'parent_key2' => 2,
                    ],
                ],
                [
                    'key1' => 2,
                    'key2' => 1,
                    'key3' => 201,
                    'child_entity' => [
                        'key1' => 1,
                        'key2' => 3,
                        'key3' => null,
                        'parent_key1' => 2,
                        'parent_key2' => 1,
                    ],
                ],
                [
                    'key1' => 2,
                    'key2' => 2,
                    'key3' => 202,
                    'child_entity' => null,
                ],
            ],
            $selector->fetchData()
        );
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

    // public function testDeleteNullableChild(): void
    // {
    //     $this->orm = $this->withSchema(new Schema([
    //         CompositePK::class    => [
    //             Schema::ROLE        => 'user',
    //             Schema::MAPPER      => Mapper::class,
    //             Schema::DATABASE    => 'default',
    //             Schema::TABLE       => 'user',
    //             Schema::PRIMARY_KEY => 'id',
    //             Schema::COLUMNS     => ['id', 'email', 'balance'],
    //             Schema::SCHEMA      => [],
    //             Schema::RELATIONS   => [
    //                 'child_entity' => [
    //                     Relation::TYPE   => Relation::HAS_ONE,
    //                     Relation::TARGET => CompositePKChild::class,
    //                     Relation::SCHEMA => [
    //                         Relation::CASCADE   => true,
    //                         Relation::NULLABLE  => true,
    //                         Relation::INNER_KEY => 'id',
    //                         Relation::OUTER_KEY => 'user_id',
    //                     ],
    //                 ]
    //             ]
    //         ],
    //         CompositePKChild::class => [
    //             Schema::ROLE        => 'child_entity',
    //             Schema::MAPPER      => Mapper::class,
    //             Schema::DATABASE    => 'default',
    //             Schema::TABLE       => 'child_entity',
    //             Schema::PRIMARY_KEY => 'id',
    //             Schema::COLUMNS     => ['id', 'user_id', 'image'],
    //             Schema::SCHEMA      => [],
    //             Schema::RELATIONS   => [
    //                 'nested' => [
    //                     Relation::TYPE   => Relation::HAS_ONE,
    //                     Relation::TARGET => Nested::class,
    //                     Relation::SCHEMA => [
    //                         Relation::CASCADE   => true,
    //                         Relation::INNER_KEY => 'id',
    //                         Relation::OUTER_KEY => 'child_entity_id',
    //                     ],
    //                 ]
    //             ]
    //         ],
    //         Nested::class  => [
    //             Schema::ROLE        => 'nested',
    //             Schema::MAPPER      => Mapper::class,
    //             Schema::DATABASE    => 'default',
    //             Schema::TABLE       => 'nested',
    //             Schema::PRIMARY_KEY => 'id',
    //             Schema::COLUMNS     => ['id', 'child_entity_id', 'label'],
    //             Schema::SCHEMA      => [],
    //             Schema::RELATIONS   => []
    //         ]
    //     ]));
    //
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity')->fetchOne();
    //     $e->child_entity = null;
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //
    //     $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity')->fetchOne();
    //
    //     $this->assertSame(null, $e->child_entity);
    //     $this->assertSame(1, (new Select($this->orm, CompositePKChild::class))->count());
    // }
    //
    // public function testAssignNewChild(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity')->fetchOne();
    //
    //     $oP = $e->child_entity;
    //     $e->child_entity = new CompositePKChild();
    //     $e->child_entity->image = 'new.jpg';
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //
    //     $this->assertFalse($this->orm->getHeap()->has($oP));
    //     $this->assertTrue($this->orm->getHeap()->has($e->child_entity));
    //
    //     $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity')->fetchOne();
    //
    //     $this->assertNotEquals($oP, $e->child_entity->id);
    //     $this->assertSame('new.jpg', $e->child_entity->image);
    // }
    //
    // public function testMoveToAnotherEntity(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     [$a, $b] = $selector->load('child_entity')->orderBy('user.id')->fetchAll();
    //
    //     $this->assertNotNull($a->child_entity);
    //     $this->assertNull($b->child_entity);
    //
    //     $p = $a->child_entity;
    //     [$b->child_entity, $a->child_entity] = [$a->child_entity, null];
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($a);
    //     $tr->persist($b);
    //     $tr->run();
    //
    //     $this->assertTrue($this->orm->getHeap()->has($b->child_entity));
    //
    //     $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
    //     [$a, $b] = $selector->load('child_entity')->orderBy('user.id')->fetchAll();
    //
    //     $this->assertNull($a->child_entity);
    //     $this->assertNotNull($b->child_entity);
    //     $this->assertEquals($p->id, $b->child_entity->id);
    // }
    //
    // public function testExchange(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     [$a, $b] = $selector->load('child_entity')->orderBy('user.id')->fetchAll();
    //
    //     $b->child_entity = new CompositePKChild();
    //     $b->child_entity->image = 'secondary.gif';
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($b);
    //     $tr->run();
    //
    //     // reset state
    //     $this->orm = $this->orm->withHeap(new Heap());
    //
    //     $selector = new Select($this->orm, CompositePK::class);
    //     [$a, $b] = $selector->load('child_entity')->orderBy('user.id')->fetchAll();
    //     $this->assertSame('image.png', $a->child_entity->image);
    //     $this->assertSame('secondary.gif', $b->child_entity->image);
    //
    //     [$a->child_entity, $b->child_entity] = [$b->child_entity, $a->child_entity];
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($a);
    //     $tr->persist($b);
    //     $tr->run();
    //
    //     // reset state
    //     $this->orm = $this->orm->withHeap(new Heap());
    //
    //     $selector = new Select($this->orm, CompositePK::class);
    //     [$a, $b] = $selector->load('child_entity')->orderBy('user.id')->fetchAll();
    //     $this->assertSame('image.png', $b->child_entity->image);
    //     $this->assertSame('secondary.gif', $a->child_entity->image);
    // }
    //
    // public function testFetchNestedRelation(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $selector->load('child_entity.nested');
    //
    //     $this->assertEquals([
    //         [
    //             'id'      => 1,
    //             'email'   => 'hello@world.com',
    //             'balance' => 100.0,
    //             'child_entity' => [
    //                 'id'      => 1,
    //                 'user_id' => 1,
    //                 'image'   => 'image.png',
    //                 'nested'  => [
    //                     'id'         => 1,
    //                     'child_entity_id' => 1,
    //                     'label'      => 'nested-label',
    //                 ]
    //             ]
    //         ],
    //         [
    //             'id'      => 2,
    //             'email'   => 'another@world.com',
    //             'balance' => 200.0,
    //             'child_entity' => null
    //         ]
    //     ], $selector->fetchData());
    // }
    //
    // public function testFetchNestedRelationPostload(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $selector->load('child_entity', ['method' => JoinableLoader::POSTLOAD]);
    //     $selector->load('child_entity.nested');
    //
    //     $this->assertEquals([
    //         [
    //             'id'      => 1,
    //             'email'   => 'hello@world.com',
    //             'balance' => 100.0,
    //             'child_entity' => [
    //                 'id'      => 1,
    //                 'user_id' => 1,
    //                 'image'   => 'image.png',
    //                 'nested'  => [
    //                     'id'         => 1,
    //                     'child_entity_id' => 1,
    //                     'label'      => 'nested-label',
    //                 ]
    //             ]
    //         ],
    //         [
    //             'id'      => 2,
    //             'email'   => 'another@world.com',
    //             'balance' => 200.0,
    //             'child_entity' => null
    //         ]
    //     ], $selector->fetchData());
    // }
    //
    // public function testUpdateNestedChild(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $e->child_entity->nested->label = 'new-label';
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //
    //     $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $this->assertSame('new-label', $e->child_entity->nested->label);
    // }
    //
    // public function testChangeNestedChild(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $e->child_entity->nested = new Nested();
    //     $e->child_entity->nested->label = 'another';
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //
    //     $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $this->assertSame('another', $e->child_entity->nested->label);
    // }
    //
    // public function testNoWriteQueries(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $e->child_entity->nested = new Nested();
    //     $e->child_entity->nested->label = 'another';
    //
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //
    //     $this->orm = $this->orm->withHeap(new Heap());
    //     $selector = new Select($this->orm, CompositePK::class);
    //     $e = $selector->wherePK(1)->load('child_entity.nested')->fetchOne();
    //
    //     $this->captureWriteQueries();
    //     $tr = new Transaction($this->orm);
    //     $tr->persist($e);
    //     $tr->run();
    //     $this->assertNumWrites(0);
    // }
    //
    // public function testFindByRelatedID(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //
    //     $selector->with('child_entity')->where('child_entity.id', 1);
    //
    //     $result = $selector->fetchAll();
    //     $this->assertCount(1, $result);
    //     $this->assertInstanceOf(CompositePK::class, $result[0]);
    //     $this->assertEquals(1, $result[0]->id);
    // }
    //
    // public function testFindByRelatedIDAliased(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //
    //     $selector->with('child_entity', ['as' => 'child_entity_relation'])->where('child_entity.id', 1);
    //
    //     $result = $selector->fetchAll();
    //     $this->assertCount(1, $result);
    //     $this->assertInstanceOf(CompositePK::class, $result[0]);
    //     $this->assertEquals(1, $result[0]->id);
    // }
    //
    // public function testFindByRelatedIDArray(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //
    //     $selector->with('child_entity')->where('child_entity.id', new Parameter([1]));
    //
    //     $result = $selector->fetchAll();
    //     $this->assertCount(1, $result);
    //     $this->assertInstanceOf(CompositePK::class, $result[0]);
    //     $this->assertEquals(1, $result[0]->id);
    // }
    //
    // public function testFindByRelatedColumn(): void
    // {
    //     $selector = new Select($this->orm, CompositePK::class);
    //
    //     $selector->with('child_entity')->where('child_entity.image', '=', 'image.png');
    //
    //     $result = $selector->fetchAll();
    //     $this->assertCount(1, $result);
    //     $this->assertInstanceOf(CompositePK::class, $result[0]);
    //     $this->assertEquals(1, $result[0]->id);
    // }
    //
    // public function testDoNotOverwriteRelation(): void
    // {
    //     $select = new Select($this->orm, CompositePK::class);
    //
    //     $u = $select->load('child_entity')->wherePK(1)->fetchOne();
    //
    //     $newCompositePKChild = new CompositePKChild();
    //     $newCompositePKChild->image = 'new';
    //     $u->child_entity = $newCompositePKChild;
    //
    //     $u2 = $this->orm->getRepository(CompositePK::class)->findByPK(1);
    //     $this->assertSame('new', $u2->child_entity->image);
    //
    //     $u3 = $this->orm->withHeap(new Heap())->getRepository(CompositePK::class)
    //                     ->select()->load('child_entity')->wherePK(1)->fetchOne();
    //
    //     $this->assertSame('image.png', $u3->child_entity->image);
    //
    //     $t = new Transaction($this->orm);
    //     $t->persist($u);
    //     $t->run();
    //
    //     $u4 = $this->orm->withHeap(new Heap())->getRepository(CompositePK::class)
    //                     ->select()->load('child_entity')->wherePK(1)->fetchOne();
    //
    //     $this->assertSame('new', $u4->child_entity->image);
    // }
    //
    // public function testOverwritePromisedRelation(): void
    // {
    //     $select = new Select($this->orm, CompositePK::class);
    //     $u = $select->wherePK(1)->fetchOne();
    //
    //     $newCompositePKChild = new CompositePKChild();
    //     $newCompositePKChild->image = 'new';
    //     $u->child_entity = $newCompositePKChild;
    //
    //     // relation is already set prior to loading
    //     $u2 = $this->orm->getRepository(CompositePK::class)
    //                     ->select()
    //                     ->load('child_entity')
    //                     ->wherePK(1)->fetchOne();
    //
    //     $this->assertSame('image.png', $u2->child_entity->image);
    //
    //     $u3 = $this->orm->withHeap(new Heap())->getRepository(CompositePK::class)
    //                     ->select()->load('child_entity')->wherePK(1)->fetchOne();
    //
    //     $this->assertSame('image.png', $u3->child_entity->image);
    //
    //     $t = new Transaction($this->orm);
    //     $t->persist($u);
    //     $t->run();
    //
    //     // ovewrite values
    //     $u4 = $this->orm->withHeap(new Heap())->getRepository(CompositePK::class)
    //                     ->select()->load('child_entity')->wherePK(1)->fetchOne();
    //
    //     $this->assertSame('image.png', $u4->child_entity->image);
    //
    //     $this->captureWriteQueries();
    //     $t = new Transaction($this->orm);
    //     $t->persist($u);
    //     $t->run();
    //
    //     $this->assertNumWrites(0);
    // }
}
