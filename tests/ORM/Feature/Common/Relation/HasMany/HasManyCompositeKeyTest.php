<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasMany;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasManyCompositeKeyTest extends OrmTestCase
{
    protected const CHILD_CONTAINER = 'children';
    protected const PARENT_1 = ['key1' => 1, 'key2' => 1, 'key3' => 101];
    protected const PARENT_2 = ['key1' => 1, 'key2' => 2, 'key3' => 102];
    protected const PARENT_3 = ['key1' => 2, 'key2' => 1, 'key3' => 201];
    protected const PARENT_4 = ['key1' => 2, 'key2' => 2, 'key3' => 202];
    protected const CHILD_1_1 = ['key1' => 1, 'key2' => 1, 'key3' => null, 'parent_key1' => 1, 'parent_key2' => 1];
    protected const CHILD_1_2 = ['key1' => 1, 'key2' => 2, 'key3' => 'foo1', 'parent_key1' => 1, 'parent_key2' => 1];
    protected const CHILD_1_3 = ['key1' => 1, 'key2' => 3, 'key3' => 'bar1', 'parent_key1' => 1, 'parent_key2' => 1];
    protected const CHILD_2_1 = ['key1' => 2, 'key2' => 1, 'key3' => 'foo2', 'parent_key1' => 1, 'parent_key2' => 2];
    protected const CHILD_3_1 = ['key1' => 3, 'key2' => 1, 'key3' => 'bar3', 'parent_key1' => 2, 'parent_key2' => 1];
    protected const PARENT_1_FULL = self::PARENT_1 + [self::CHILD_CONTAINER => [self::CHILD_1_1, self::CHILD_1_2, self::CHILD_1_3]];
    protected const PARENT_2_FULL = self::PARENT_2 + [self::CHILD_CONTAINER => [self::CHILD_2_1]];
    protected const PARENT_3_FULL = self::PARENT_3 + [self::CHILD_CONTAINER => [self::CHILD_3_1]];
    protected const PARENT_4_FULL = self::PARENT_4 + [self::CHILD_CONTAINER => []];
    protected const SET_FULL = [
        self::PARENT_1_FULL,
        self::PARENT_2_FULL,
        self::PARENT_3_FULL,
        self::PARENT_4_FULL,
    ];

    #[Test]
    public function testInitRelation(): void
    {
        $u = $this->orm->make(CompositePK::class);
        Assert::instanceOf($u->children, ArrayCollection::class);
    }

    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3');

        Assert::equals($selector->fetchData(), self::SET_FULL);
    }

    #[Test]
    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER, ['method' => JoinableLoader::INLOAD])->orderBy('parent_entity.key3');

        Assert::equals($selector->fetchData(), self::SET_FULL);
    }

    #[Test]
    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        $data = $selector->with(self::CHILD_CONTAINER)->buildQuery()->fetchAll();
        // only 3 parents have children
        Assert::count($data[0], 3);
    }

    #[Test]
    public function testAccessRelated(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b, $c, $d] = $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3')->fetchAll();

        Assert::instanceOf($a->children, Collection::class);
        Assert::instanceOf($b->children, Collection::class);

        Assert::count($a->children, 3);
        Assert::count($b->children, 1);
        Assert::count($c->children, 1);
        Assert::count($d->children, 0);

        Assert::same($a->children[0]->key3, self::CHILD_1_1['key3']);
        Assert::same($a->children[1]->key3, self::CHILD_1_2['key3']);
        Assert::same($a->children[2]->key3, self::CHILD_1_3['key3']);
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, CompositePK::class);
        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b] = $selector->load(self::CHILD_CONTAINER)->orderBy('parent_entity.key3')->fetchAll();

        QueryAssert::writes(0, fn() => (new Transaction($this->orm))
            ->persist($a)
            ->persist($b)
            ->run());
    }

    #[Test]
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
        QueryAssert::writes(3, fn() => (new Transaction($this->orm))->persist($e)->run());
        // consecutive test
        QueryAssert::writes(0, fn() => (new Transaction($this->orm))->persist($e)->run());

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        Assert::true($this->orm->getHeap()->has($e->children[0]));
        Assert::same($this->orm->getHeap()->get($e->children[0])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->children[0])->getData()['parent_key1'], $e->key1);
        Assert::same($this->orm->getHeap()->get($e->children[0])->getData()['parent_key2'], $e->key2);

        Assert::true($this->orm->getHeap()->has($e->children[1]));
        Assert::same($this->orm->getHeap()->get($e->children[1])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->children[1])->getData()['parent_key1'], $e->key1);
        Assert::same($this->orm->getHeap()->get($e->children[1])->getData()['parent_key2'], $e->key2);

        $selector = new Select($this->orm, CompositePK::class);
        $selector->load(self::CHILD_CONTAINER);

        Assert::equals($selector->wherePK([91, 91])->fetchData(), [
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
        ]);
    }

    #[Test]
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

        Assert::count($e->children, 2);

        Assert::same($e->children[0]->key3, self::CHILD_1_1['key3']);
        Assert::same($e->children[1]->key3, self::CHILD_1_3['key3']);
    }

    #[Test]
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

        Assert::count($e->children, 2);

        Assert::same($e->children[0]->key3, self::CHILD_1_1['key3']);
        Assert::same($e->children[1]->key3, self::CHILD_1_3['key3']);

        Assert::same((new Select($this->orm, CompositePKChild::class))->count(), 5);
    }

    #[Test]
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
        QueryAssert::writes(2, fn() => $this->save($e));
        // consecutive test
        QueryAssert::writes(0, fn() => $this->save($e));

        $selector = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        /** @var CompositePK $e */
        $e = $selector->wherePK([1, 1])->fetchOne();

        Assert::count($e->children, 3);

        Assert::same($e->children[0]->key3, self::CHILD_1_1['key3']);
        Assert::same($e->children[1]->key3, self::CHILD_1_3['key3']);
        Assert::same($e->children[2]->key3, 'fiz');
    }

    #[Test]
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

        Assert::count($a->children, 3);
        Assert::count($d->children, 0);

        $d->children = $a->children->slice(0, 2);
        foreach ($d->children as $c) {
            $a->children->removeElement($c);
        }

        $d->children[0]->key3 = 'new value';

        Assert::count($a->children, 1);
        Assert::count($d->children, 2);
        QueryAssert::writes(2, fn() => (new Transaction($this->orm))->persist($a)->persist($d)->run());
        // consecutive
        QueryAssert::writes(0, fn() => (new Transaction($this->orm))->persist($a)->persist($d)->run());

        $selector = new Select($this->orm->withHeap(new Heap()), CompositePK::class);

        /**
         * @var CompositePK $a
         * @var CompositePK $b
         */
        [$a, $b, $c, $d] = $selector->load(self::CHILD_CONTAINER, [
            'method' => JoinableLoader::INLOAD,
            'as' => 'child',
        ])->orderBy('parent_entity.key3')->fetchAll();

        Assert::count($a->children, 1);
        Assert::count($d->children, 2);

        Assert::same($a->children[0]->key1, self::CHILD_1_3['key1']);
        Assert::same($a->children[0]->key2, self::CHILD_1_3['key2']);
        Assert::same($d->children[0]->key1, self::CHILD_1_1['key1']);
        Assert::same($d->children[0]->key2, self::CHILD_1_1['key2']);
        Assert::same($d->children[1]->key1, self::CHILD_1_2['key1']);
        Assert::same($d->children[1]->key2, self::CHILD_1_2['key2']);

        Assert::same($d->children[0]->key3, 'new value');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable(
            'parent_entity',
            [
                'pField1' => 'bigInteger,primary',
                'pField2' => 'bigInteger,primary',
                'pField3' => 'integer,nullable',
            ],
        );

        $this->makeTable(
            'child_entity',
            [
                'field1' => 'bigInteger,primary',
                'field2' => 'bigInteger,primary',
                'field3' => 'string,nullable',
                'parent_field1' => 'bigInteger,null',
                'parent_field2' => 'bigInteger,null',
            ],
        );

        $this->makeCompositeFK(
            'child_entity',
            ['parent_field1', 'parent_field2'],
            'parent_entity',
            ['pField1', 'pField2'],
        );

        $this->getDatabase()->table('parent_entity')->insertMultiple(
            ['pField1', 'pField2', 'pField3'],
            [
                self::PARENT_1,
                self::PARENT_2,
                self::PARENT_3,
                self::PARENT_4,
            ],
        );
        $this->getDatabase()->table('child_entity')->insertMultiple(
            ['field1', 'field2', 'field3', 'parent_field1', 'parent_field2'],
            [
                self::CHILD_1_1,
                self::CHILD_1_2,
                self::CHILD_1_3,
                self::CHILD_2_1,
                self::CHILD_3_1,
            ],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
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
