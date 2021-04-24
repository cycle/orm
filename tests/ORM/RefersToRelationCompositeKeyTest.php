<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Exception\TransactionException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CompositePK;
use Cycle\ORM\Tests\Fixtures\CompositePKChild;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class RefersToRelationCompositeKeyTest extends BaseTest
{
    use TableTrait;

    protected const
        CHILD_CONTAINER = 'child_entity',
        CHILDREN_CONTAINER = 'children';

    public function setUp(): void
    {
        parent::setUp();

        $this->dropDatabase();
        $this->makeTable(
            'parent_entity',
            [
                'pField1' => 'bigInteger,primary',
                'pField2' => 'bigInteger,primary',
                'pField3' => 'integer,nullable',
                'cField1' => 'bigInteger,nullable',
                'cField2' => 'bigInteger,nullable',
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

        $this->makeCompositeFK('child_entity', ['parent_field1', 'parent_field2'], 'parent_entity', ['pField1', 'pField2']);

        $this->orm = $this->withSchema(new Schema($this->getSchemaArray()));
    }

    public function testCreateUserWithDoubleReference(): void
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
        $this->logger->hide();

        $u = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        /** @var CompositePK $u */
        $this->assertSame($u->child_key1, $c->key1);
        $this->assertSame($u->child_key2, $c->key2);
        $this->assertNotNull($u->child_entity);
        $this->assertSame($u->child_entity, $u->children[0]);
    }

    public function testCreateUserToExistedReference(): void
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

        $u2 = new CompositePK();
        $u2->key1 = 300;
        $u2->key2 = 301;
        $u2->key3 = 303;
        $u2->child_entity = $c;

        $this->captureWriteQueries();
        (new Transaction($this->orm))->persist($u2)->run();
        $this->assertNumWrites(1);

        $u3 = (new Select($this->orm->withHeap(new Heap()), CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([300, 301])
            ->fetchOne();

        $this->assertNotNull($u3->child_entity);
        $this->assertEquals($u3->child_entity->key1, $u->children[0]->key1);
        $this->assertEquals($u3->child_entity->key2, $u->children[0]->key2);
    }

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

        $this->assertNotNull($u->child_entity);
        $this->assertSame($u->child_entity, $u->children[0]);
    }

    public function testCreateWithoutProperDependency(): void
    {
        $this->expectException(TransactionException::class);

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
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

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

        $this->assertNull($u->child_entity);
        $this->assertCount(1, $u->children);

        $u->child_entity = $u->children[0];

        (new Transaction($this->orm))->persist($u)->run();

        $u = (new Select($this->orm, CompositePK::class))
            ->load(self::CHILD_CONTAINER)
            ->load(self::CHILDREN_CONTAINER)
            ->wherePK([900, 901])
            ->fetchOne();

        $this->assertNotNull($u->child_entity);
        $this->assertCount(1, $u->children);
        $this->assertSame($u->child_entity, $u->children[0]);
    }

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

        $this->assertNotNull($u->child_entity);
        $this->assertCount(1, $u->children);
        $this->assertSame($u->child_entity, $u->children[0]);

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

        $this->assertNull($u->child_entity);
        $this->assertCount(1, $u->children);
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
                    'child_key1' => 'cField1',
                    'child_key2' => 'cField2',
                ],
                Schema::TYPECAST    => [
                    'key1' => 'int',
                    'key2' => 'int',
                    'key3' => 'int',
                    'child_key1' => 'int',
                    'child_key2' => 'int',
                ],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    self::CHILD_CONTAINER => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => ['child_key1', 'child_key2'],
                            Relation::OUTER_KEY => ['key1', 'key2'],
                        ],
                    ],
                    self::CHILDREN_CONTAINER => [
                        Relation::TYPE   => Relation::HAS_MANY,
                        Relation::TARGET => CompositePKChild::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => ['key1', 'key2'],
                            Relation::OUTER_KEY => ['parent_key1', 'parent_key2'],
                        ],
                    ],

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
            ],
        ];
    }
}
