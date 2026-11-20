<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\RefersTo;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class RefersToRelationCompositeKeyTest extends OrmTestCase
{
    protected const CHILD_CONTAINER = 'child_entity';
    protected const CHILDREN_CONTAINER = 'children';

    #[Test]
    public function testCreateParentWithDoubleReference(): void
    {
        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';

        $u->child_entity = $c;
        $u->children->add($c);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(3);

        $u = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        /** @var CompositePK $u */
        Assert::same($c->key1, $u->child_key1);
        Assert::same($c->key2, $u->child_key2);
        Assert::notNull($u->child_entity);
        Assert::same($u->children[0], $u->child_entity);
    }

    #[Test]
    public function testUpdateParentPKWithDoubleReference(): void
    {
        $p = new CompositePK();
        $p->key1 = 900;
        $p->key2 = 901;
        $p->key3 = 909;
        $this->save($p);

        // Change PK
        $p->key1 = 300;
        $p->key2 = 400;
        // Create Child
        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';
        // Link Child with Parent
        $p->child_entity = $c;
        $p->children->add($c);

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);

        $data = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->fetchAll();

        Assert::count($data, 1);
        Assert::same($c->key1, $data[0]->child_key1);
        Assert::same($c->key2, $data[0]->child_key2);
        Assert::notNull($data[0]->child_entity);
        Assert::same($data[0]->children[0], $data[0]->child_entity);
    }

    #[Test]
    public function testCreateParentToExistedReference(): void
    {
        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';

        $u->child_entity = $c;
        $u->children->add($c);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);

        $u2 = new CompositePK();
        $u2->key1 = 300;
        $u2->key2 = 301;
        $u2->key3 = 303;
        $u2->child_entity = $c;

        $this->captureWriteQueries();
        $this->save($u2);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($u2);
        $this->assertNumWrites(0);

        $u3 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([300, 301])
            ->fetchOne();

        Assert::notNull($u3->child_entity);
        Assert::equals($u->children[0]->key1, $u3->child_entity->key1);
        Assert::equals($u->children[0]->key2, $u3->child_entity->key2);
    }

    #[Test]
    public function testCreateWhenParentExists(): void
    {
        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        (new Transaction($this->orm))->persist($u)->run();

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';

        $u->child_entity = $c;
        $u->children->add($c);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($u)->run();
        $this->assertNumWrites(2);

        $s = new Select($this->orm->withHeap(new Heap()), CompositePK::class);
        $u = $s->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        Assert::notNull($u->child_entity);
        Assert::same($u->children[0], $u->child_entity);
    }

    #[Test]
    public function testCreateWithoutProperDependency(): void
    {
        Expect::exception(TransactionException::class);

        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';

        $u->child_entity = $c;
        try {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        } finally {
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

    #[Test]
    public function testAssignParentAsUpdate(): void
    {
        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';
        $u->children->add($c);

        (new Transaction($this->orm))->persist($u)->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        Assert::null($u->child_entity);
        Assert::count($u->children, 1);

        $u->child_entity = $u->children[0];

        (new Transaction($this->orm))->persist($u)->run();

        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        Assert::notNull($u->child_entity);
        Assert::count($u->children, 1);
        Assert::same($u->children[0], $u->child_entity);
    }

    #[Test]
    public function testSetNull(): void
    {
        $u = new CompositePK();
        $u->key1 = 900;
        $u->key2 = 901;
        $u->key3 = 909;

        $c = new CompositePKChild();
        $c->key1 = 500;
        $c->key2 = 501;
        $c->key3 = 'last comment';
        $u->child_entity = $c;
        $u->children->add($c);

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($u)->run();
        $this->assertNumWrites(3);

        $this->orm = $this->orm->withHeap(new Heap());
        $s = new Select($this->orm, CompositePK::class);
        $u = $s->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        Assert::notNull($u->child_entity);
        Assert::count($u->children, 1);
        Assert::same($u->children[0], $u->child_entity);

        $u->child_entity = null;

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($u)->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());

        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        Assert::null($u->child_entity);
        Assert::count($u->children, 1);
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
                'cField1' => 'bigInteger,nullable',
                'cField2' => 'bigInteger,nullable',
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
                    'child_key1' => 'cField1',
                    'child_key2' => 'cField2',
                ],
                Schema::TYPECAST => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'key3' => 'int',
                    'child_key1' => 'int',
                    'child_key2' => 'int',
                ],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['child_key1', 'child_key2'],
                            Relation::OUTER_KEY => ['key1', 'key2'],
                        ],
                    ],
                    self::CHILDREN_CONTAINER => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => ['key1', 'key2'],
                            Relation::OUTER_KEY => ['parent_key1', 'parent_key2'],
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
