<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\BelongsTo;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Fixtures\CompositePKNested;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Cycle\Database\Injection\Parameter;

abstract class BelongsToCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const
        PARENT_CONTAINER = 'parent';
    protected const
        CHILD_CONTAINER = 'child_entity';
    protected const
        NESTED_CONTAINER = 'nested';
    protected const
        PARENT_1 = ['key1' => 1, 'key2' => 1, 'key3' => 101];
    protected const
        PARENT_2 = ['key1' => 1, 'key2' => 2, 'key3' => 102];
    protected const
        PARENT_3 = ['key1' => 2, 'key2' => 1, 'key3' => 201];
    protected const
        CHILD_1 = ['key1' => 1, 'key2' => 1, 'key3' => null,   'parent_key1' => 1, 'parent_key2' => 1];
    protected const
        CHILD_2 = ['key1' => 1, 'key2' => 2, 'key3' => 'foo2', 'parent_key1' => 1, 'parent_key2' => 2];
    protected const
        CHILD_3 = ['key1' => 1, 'key2' => 3, 'key3' => 'bar3', 'parent_key1' => 1, 'parent_key2' => 2];
    protected const
        NESTED_1 = ['key3' => 'foo', 'parent_key1' => 1, 'parent_key2' => 1];
    protected const
        CHILD_1_LOADED = self::CHILD_1 + [self::PARENT_CONTAINER => self::PARENT_1];
    protected const
        CHILD_2_LOADED = self::CHILD_2 + [self::PARENT_CONTAINER => self::PARENT_2];
    protected const
        CHILD_3_LOADED = self::CHILD_3 + [self::PARENT_CONTAINER => self::PARENT_2];
    protected const
        CHILDREN_LOADED = [
            self::CHILD_1_LOADED,
            self::CHILD_2_LOADED,
            self::CHILD_3_LOADED,
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

    public function testFetchRelation(): void
    {
        $selector = (new Select($this->orm, CompositePKChild::class))->load(self::PARENT_CONTAINER);

        $this->assertEquals(self::CHILDREN_LOADED, $selector->fetchData());
    }

    public function testFetchLimitAndSortByParent(): void
    {
        $e = (new Select($this->orm, CompositePKChild::class));

        foreach ($e as $ee) {
        }

        $selector = (new Select($this->orm, CompositePKChild::class))
            ->with(self::PARENT_CONTAINER, ['as' => 'parent_entity'])
            ->load(self::PARENT_CONTAINER, ['using' => 'parent_entity'])
            ->orderBy(['parent_entity.key3' => 'ASC'])
            ->limit(1);

        $this->assertEquals([self::CHILD_1_LOADED], $selector->fetchData());
    }

    public function testWithNoColumns(): void
    {
        $data = (new Select($this->orm, CompositePKChild::class))
            ->with(self::PARENT_CONTAINER)
            ->buildQuery()
            ->fetchAll();

        // 2 local PK + 2 parent PK + 1 custom field
        $this->assertCount(5, $data[0]);
    }

    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, CompositePKChild::class);
        $selector->load(self::PARENT_CONTAINER, ['method' => Select\JoinableLoader::INLOAD])
            ->orderBy('child_entity.key1', 'ASC')
            ->orderBy('child_entity.key2', 'ASC');

        $this->assertEquals(self::CHILDREN_LOADED, $selector->fetchData());
    }

    public function testAccessEntities(): void
    {
        $result = (new Select($this->orm, CompositePKChild::class))
            ->load(self::PARENT_CONTAINER)
            ->orderBy('child_entity.key1', 'ASC')
            ->orderBy('child_entity.key2', 'ASC')
            ->fetchAll();

        $this->assertInstanceOf(CompositePKChild::class, $result[0]);
        $this->assertInstanceOf(CompositePK::class, $result[0]->parent);
        $this->assertSame(self::PARENT_1['key3'], $result[0]->parent->key3);

        $this->assertInstanceOf(CompositePKChild::class, $result[1]);
        $this->assertInstanceOf(CompositePK::class, $result[1]->parent);
        $this->assertSame(self::PARENT_2['key3'], $result[1]->parent->key3);

        $this->assertInstanceOf(CompositePKChild::class, $result[2]);
        $this->assertInstanceOf(CompositePK::class, $result[2]->parent);
        $this->assertEquals(self::PARENT_2['key3'], $result[2]->parent->key3);

        $this->assertSame($result[1]->parent, $result[2]->parent);
    }

    public function testCreateWithRelations(): void
    {
        $u = new CompositePK();
        $u->key1 = 90;
        $u->key2 = 92;
        $u->key3 = 9092;

        $p = new CompositePKChild();
        $p->key1 = 81;
        $p->key2 = 82;
        $p->key3 = 'magic.gif';
        $p->parent = $u;

        $this->save($p);

        $this->assertTrue($this->orm->getHeap()->has($u));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($u)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($p));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($p)->getStatus());

        $this->assertSame($u->key1, $this->orm->getHeap()->get($p)->getData()['parent_key1']);
        $this->assertSame($u->key2, $this->orm->getHeap()->get($p)->getData()['parent_key2']);

        $selector = (new Select($this->orm, CompositePKChild::class))
            ->load(self::PARENT_CONTAINER)
            ->wherePK([81, 82]);

        $this->assertEquals($p, $selector->fetchOne());
    }

    public function testNoWriteQueries(): void
    {
        $u = new CompositePK();
        $u->key1 = 101;
        $u->key2 = 101;
        $u->key3 = 300;

        $p = new CompositePKChild();
        $p->key1 = 201;
        $p->key2 = 201;
        $p->parent = $u;

        (new Transaction($this->orm))->persist($p)->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, CompositePKChild::class))
            ->load(self::PARENT_CONTAINER)
            ->wherePK([201, 201])
            ->fetchOne();

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($p)->run();
        $this->assertNumWrites(0);
    }

    public function testSetExistedParent(): void
    {
        $s = new Select($this->orm, CompositePK::class);
        $u = $s->wherePK([1, 1])->fetchOne();

        $p = new CompositePKChild();
        $p->key1 = 202;
        $p->key2 = 202;
        $p->key3 = 'magic.gif';
        $p->parent = $u;

        (new Transaction($this->orm))->persist($p)->run();

        $this->assertEquals([1 ,1], [$u->key1, $u->key2]);
        $this->assertEquals([202, 202], [$p->key1, $p->key2]);

        $this->assertTrue($this->orm->getHeap()->has($p));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($p)->getStatus());

        $this->assertSame($u->key1, $this->orm->getHeap()->get($p)->getData()['parent_key1']);
        $this->assertSame($u->key2, $this->orm->getHeap()->get($p)->getData()['parent_key2']);

        $selector = (new Select($this->orm, CompositePKChild::class))->load(self::PARENT_CONTAINER);

        $this->assertEquals([
            [
                'key1' => 202,
                'key2' => 202,
                'key3' => 'magic.gif',
                'parent_key1' => 1,
                'parent_key2' => 1,
                'parent' => self::PARENT_1,
            ],
        ], $selector->wherePK([202, 202])->fetchData());
    }

    public function testChangeParent(): void
    {
        $p = (new Select($this->orm, CompositePKChild::class))
            ->wherePK([1, 1])
            ->load(self::PARENT_CONTAINER)
            ->fetchOne();
        $u = (new Select($this->orm, CompositePK::class))
            ->wherePK([1, 2])
            ->fetchOne();
        $p->parent = $u;

        (new Transaction($this->orm))->persist($p)->run();

        $this->assertEquals([
            [
                'parent_key1' => self::PARENT_2['key1'],
                'parent_key2' => self::PARENT_2['key2'],
                'parent' => self::PARENT_2,
            ] + self::CHILD_1,
        ], (new Select($this->orm, CompositePKChild::class))
            ->load(self::PARENT_CONTAINER)
            ->wherePK([1, 1])->fetchData());
    }

    public function testExchangeParents(): void
    {
        /**
         * @var CompositePKChild $a
         * @var CompositePKChild $b
         */
        [$a, $b] = (new Select($this->orm, CompositePKChild::class))
            ->wherePK([1, 1], [1, 2])
            ->orderBy('child_entity.key1', 'ASC')
            ->orderBy('child_entity.key2', 'ASC')
            ->load(self::PARENT_CONTAINER)
            ->fetchAll();

        [$a->parent, $b->parent] = [$b->parent, $a->parent];


        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(2);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);

        $s = new Select($this->orm->withHeap(new Heap()), CompositePKChild::class);
        [$a2, $b2] = $s->wherePK([1, 1], [1, 2])
            ->orderBy('child_entity.key1', 'ASC')
            ->orderBy('child_entity.key2', 'ASC')
            ->load(self::PARENT_CONTAINER)
            ->fetchAll();

        $this->assertSame($a->parent->key1, $a2->parent->key1);
        $this->assertSame($a->parent->key2, $a2->parent->key2);
        $this->assertSame($b->parent->key1, $b2->parent->key1);
        $this->assertSame($b->parent->key2, $b2->parent->key2);
    }

    public function testSetNullException(): void
    {
        $this->expectException(NullException::class);

        $p = (new Select($this->orm, CompositePKChild::class))
            ->wherePK([1, 1])->load(self::PARENT_CONTAINER)->fetchOne();
        $p->parent = null;

        try {
            (new Transaction($this->orm))->persist($p)->run();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // we do not expect state to be consistent as transaction failed, see rollback tests
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

    public function testCompositePKNested(): void
    {
        $n = new CompositePKNested();
        $n->key3 = 'nested label';
        $n->parent = new CompositePKChild();
        $n->parent->key1 = 123;
        $n->parent->key2 = 321;
        $n->parent->key3 = 'profile';
        $n->parent->parent = new CompositePK();
        $n->parent->parent->key1 = 241;
        $n->parent->parent->key2 = 242;
        $n->parent->parent->key3 = 999;

        $this->captureWriteQueries();
        $this->save($n);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($n);
        $this->assertNumWrites(0);

        $n = (new Select($this->orm->withHeap(new Heap()), CompositePKNested::class))
            ->wherePK(new Parameter($n->key1))
            ->load(self::PARENT_CONTAINER . '.' . self::PARENT_CONTAINER)
            ->fetchOne();

        $this->assertSame('profile', $n->parent->key3);
        $this->assertSame(999, $n->parent->parent->key3);
    }

    public function testWhereCompositePKNested(): void
    {
        $n = (new Select($this->orm->withHeap(new Heap()), CompositePKNested::class))
            ->with('parent.parent')
            ->where('parent.parent.key1', self::PARENT_1['key1'])
            ->where('parent.parent.key2', self::PARENT_1['key2'])
            ->fetchOne();

        $this->assertSame('foo', $n->key3);
    }

    public function testWhereCompositePKNestedWithAlias(): void
    {
        $n = (new Select($this->orm->withHeap(new Heap()), CompositePKNested::class))
            ->with('parent.parent', ['as' => 'u'])
            ->where('u.key1', self::PARENT_1['key1'])
            ->where('u.key2', self::PARENT_1['key2'])
            ->fetchOne();

        $this->assertSame('foo', $n->key3);
    }

    private function getSchemaArray(): array
    {
        return [
            CompositePK::class => [
                Schema::ROLE => 'parent_entity',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'parent_entity',
                Schema::MAPPER => Mapper::class,
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
                Schema::RELATIONS => [],
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
                Schema::RELATIONS => [
                    self::PARENT_CONTAINER => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => CompositePK::class,
                        Relation::SCHEMA => [
                            Relation::NULLABLE => false,
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['parent_key1', 'parent_key2'],
                            Relation::OUTER_KEY => ['key1', 'key2'],
                        ],
                    ],
                ],
            ],
            CompositePKNested::class => [
                Schema::ROLE => 'nested_entity',
                Schema::DATABASE => 'default',
                Schema::TABLE => 'nested_entity',
                Schema::MAPPER => Mapper::class,
                Schema::PRIMARY_KEY => ['key1'],
                Schema::COLUMNS => [
                    'key1' => 'field1',
                    'key3' => 'field3',
                    'parent_key1' => 'parent_field1',
                    'parent_key2' => 'parent_field2',
                ],
                Schema::TYPECAST => [
                    'key1' => 'int',
                    'parent_key1' => 'int',
                    'parent_key2' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    self::PARENT_CONTAINER => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['parent_key1', 'parent_key2'],
                            Relation::OUTER_KEY => ['key1', 'key2'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
