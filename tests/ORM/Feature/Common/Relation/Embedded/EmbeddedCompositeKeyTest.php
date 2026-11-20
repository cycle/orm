<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Embedded;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class EmbeddedCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const CHILD_CONTAINER = 'child_entity';
    protected const CHILD_ROLE = 'parent_entity:' . self::CHILD_CONTAINER;
    protected const KEY_1 = ['key1' => 1, 'key2' => 1];
    protected const KEY_2 = ['key1' => 1, 'key2' => 2];
    protected const KEY_3 = ['key1' => 2, 'key2' => 1];
    protected const PARENT_1 = ['key3' => 1];
    protected const PARENT_2 = ['key3' => 2];
    protected const PARENT_3 = ['key3' => 3];
    protected const CHILD_1 = ['key3' => 'foo'];
    protected const CHILD_2 = ['key3' => 'bar'];
    protected const CHILD_3 = ['key3' => 'baz'];
    protected const ALL_LOADED = [
        self::KEY_1 + self::PARENT_1 + [self::CHILD_CONTAINER => self::CHILD_1],
        self::KEY_2 + self::PARENT_2 + [self::CHILD_CONTAINER => self::CHILD_2],
        self::KEY_3 + self::PARENT_3 + [self::CHILD_CONTAINER => self::CHILD_3],
    ];

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        \Testo\Assert::equals($selector->fetchData(), self::ALL_LOADED);
    }

    #[\Testo\Test]
    public function testInitRelation(): void
    {
        $selector = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER);

        [$a, $b, $c] = $selector->fetchAll();

        \Testo\Assert::instanceOf($a->child_entity, CompositePKChild::class);
        \Testo\Assert::instanceOf($b->child_entity, CompositePKChild::class);

        \Testo\Assert::same($a->child_entity->key3, 'foo');
        \Testo\Assert::same($b->child_entity->key3, 'bar');
        \Testo\Assert::same($c->child_entity->key3, 'baz');
    }

    #[\Testo\Test]
    public function testInitRelationFetchOne(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        \Testo\Assert::instanceOf($u->child_entity, CompositePKChild::class);
        \Testo\Assert::same($u->child_entity->key3, 'foo');
    }

    #[\Testo\Test]
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

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u->key1, $u->key2])
            ->fetchOne();

        \Testo\Assert::same($u2->key1, $u->key1);
        \Testo\Assert::same($u2->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key1, $u->key1);
        \Testo\Assert::same($u2->child_entity->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key3, 'user3');
    }

    #[\Testo\Test]
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

    #[\Testo\Test]
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
            ],
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

        \Testo\Assert::equals($u2->key1, $u->key1);
        \Testo\Assert::equals($u2->key2, $u->key2);
        \Testo\Assert::equals($u2->key3, 808);
        \Testo\Assert::same($u2->child_entity->key3, 'newpass');
    }

    #[\Testo\Test]
    public function testInitRelationReferenceNothing(): void
    {
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')->fetchOne();

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
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

        \Testo\Assert::equals($u2->key1, $u->key1);
        \Testo\Assert::equals($u2->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key3, 'altered');
    }

    #[\Testo\Test]
    public function testResolvePromise(): void
    {
        /** @var CompositePK $u */
        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')
            ->fetchOne();

        \Testo\Assert::same($u->child_entity->key3, 'foo');
    }

    #[\Testo\Test]
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

        \Testo\Assert::equals($u2->key1, $u->key1);
        \Testo\Assert::equals($u2->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key3, 'user3');
    }

    #[\Testo\Test]
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

    #[\Testo\Test]
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

        \Testo\Assert::equals($u3->key1, $u->key1);
        \Testo\Assert::equals($u3->key2, $u->key2);
        \Testo\Assert::same($u3->child_entity->key3, 'bar');

        $u4 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u2->key1, $u2->key2])
            ->fetchOne();

        // unchanged
        \Testo\Assert::equals($u4->key1, $u2->key1);
        \Testo\Assert::equals($u4->key2, $u2->key2);
        \Testo\Assert::same($u4->child_entity->key3, 'bar');
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($u3->key1, $u->key1);
        \Testo\Assert::same($u3->key2, $u->key2);
        \Testo\Assert::same($u3->child_entity->key3, 'bar');

        $u4 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->wherePK([$u2->key1, $u2->key2])
            ->fetchOne();

        // unchanged
        \Testo\Assert::same($u4->key1, $u2->key1);
        \Testo\Assert::same($u4->key2, $u2->key2);
        \Testo\Assert::same($u4->child_entity->key3, 'bar');
    }

    #[\Testo\Test]
    public function testSelectEmbeddable(): void
    {
        $u = (new Select($this->orm, CompositePKChild::class))
            ->orderBy('key1', 'ASC')
            ->orderBy('key2', 'ASC')
            ->fetchOne();

        \Testo\Assert::same($u->key3, 'foo');
        \Testo\Assert::same([$u->key1, $u->key2], array_values(self::KEY_1));
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($u2->key1, $u->key1);
        \Testo\Assert::same($u2->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key3, 'abc');
    }

    #[\Testo\Test]
    public function testNullify(): void
    {
        \Testo\Expect::exception(NullException::class);

        $u = (new Select($this->orm, CompositePK::class))
            ->orderBy('key3', 'ASC')->fetchOne();

        $u->child_entity = null;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        $u2 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)->wherePK([$u->key1, $u->key2])->fetchOne();

        \Testo\Assert::same($u2->key1, $u->key1);
        \Testo\Assert::same($u2->key2, $u->key2);
        \Testo\Assert::same($u2->child_entity->key3, 'user3');
    }

    #[\Testo\Lifecycle\BeforeTest]
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
            ],
        );

        $this->getDatabase()->table('parent_entity')->insertMultiple(
            ['pField1', 'pField2', 'pField3', 'cField3'],
            [
                array_merge(self::KEY_1, array_values(self::PARENT_1), array_values(self::CHILD_1)),
                array_merge(self::KEY_2, array_values(self::PARENT_2), array_values(self::CHILD_2)),
                array_merge(self::KEY_3, array_values(self::PARENT_3), array_values(self::CHILD_3)),
            ],
        );

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
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
                Schema::RELATIONS => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE => Relation::EMBEDDED,
                        Relation::TARGET => self::CHILD_ROLE,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [],
                    ],
                ],
            ],
            CompositePKChild::class => [
                Schema::ROLE => self::CHILD_ROLE,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'parent_entity',
                Schema::MAPPER => Mapper::class,
                Schema::PRIMARY_KEY => ['key1', 'key2'],
                Schema::COLUMNS => [
                    'key1' => 'pField1',
                    'key2' => 'pField2',
                    'key3' => 'cField3',
                ],
                Schema::TYPECAST => [
                    'key1' => 'int',
                    'key2' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ];
    }
}
