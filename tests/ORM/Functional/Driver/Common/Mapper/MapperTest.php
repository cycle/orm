<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Admin as User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class MapperTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float,nullable',
                'protected' => 'int,nullable',
                'private' => 'int,nullable',
            ]
        );

        $this->makeTable(
            'post',
            [
                'id' => 'primary',
                'user_id' => 'int',
            ]
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'protected', 'private'],
            [
                ['hello@world.com', 100, 12, 13],
                ['another@world.com', 200, 14, 15],
            ]
        );
        $this->getDatabase()->table('post')->insertMultiple(
            ['user_id'],
            [
                [1],
                [1],
                [1],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        SchemaInterface::ROLE => 'user',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'user',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'email', 'balance', 'protected', 'private'],
                        SchemaInterface::TYPECAST => ['balance' => 'float'],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [
                            'protectedRelation' => [
                                Relation::TYPE => Relation::HAS_MANY,
                                Relation::TARGET => 'post',
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::NULLABLE => false,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user',
                                ],
                            ],
                            'privateRelation' => [
                                Relation::TYPE => Relation::HAS_MANY,
                                Relation::TARGET => 'post',
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::NULLABLE => false,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user',
                                ],
                            ],
                        ],
                    ],
                    Post::class => [
                        SchemaInterface::ROLE => 'post',
                        SchemaInterface::MAPPER => Mapper::class,
                        SchemaInterface::DATABASE => 'default',
                        SchemaInterface::TABLE => 'post',
                        SchemaInterface::PRIMARY_KEY => 'id',
                        SchemaInterface::COLUMNS => ['id', 'user' => 'user_id'],
                        SchemaInterface::SCHEMA => [],
                        SchemaInterface::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);

        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                    'protected' => 12,
                    'private' => 13,
                    'protectedRelation' => [
                        ['user' => 1, 'id' => 1],
                        ['user' => 1, 'id' => 2],
                        ['user' => 1, 'id' => 3],
                    ],
                    'privateRelation' => [
                        ['user' => 1, 'id' => 1],
                        ['user' => 1, 'id' => 2],
                        ['user' => 1, 'id' => 3],
                    ],
                ],
                [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                    'protected' => 14,
                    'private' => 15,
                    'protectedRelation' => [],
                    'privateRelation' => [],
                ],
            ],
            $selector->fetchData()
        );
    }

    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testMakeByRole(): void
    {
        $this->assertInstanceOf(User::class, $this->orm->make('user'));
    }

    public function testMakeByClass(): void
    {
        $this->assertInstanceOf(User::class, $this->orm->make(User::class));
    }

    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
        $this->assertEquals(12, $result->getProtected());
        $this->assertEquals(13, $result->getPrivate());

        $data = $this->orm->getMapper($result)->fetchFields($result);
        $this->assertEquals($data, [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
            'protected' => 12,
            'private' => 13,
        ]);
    }

    public function testFetchOneWithRelation(): void
    {
        $result = (new Select($this->orm, User::class))
            ->load('privateRelation')
            ->load('protectedRelation')
            ->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
        $this->assertEquals(12, $result->getProtected());
        $this->assertEquals(13, $result->getPrivate());
        $this->assertCount(3, $result->getPrivateRelation());
        $this->assertCount(3, $result->getProtectedRelation());

        $data = $this->orm->getMapper($result)->fetchFields($result);
        $this->assertEquals($data, [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
            'protected' => 12,
            'private' => 13,
        ]);
    }

    public function testFetchSame(): void
    {
        $u = new User();
        $u->email = 'test';
        $u->balance = 100;
        $u->setProtectedRelation([]);
        $u->setPrivateRelation([]);

        $this->save($u);

        $result = (new Select($this->orm, User::class))
            ->orderBy('id', 'DESC')
            ->fetchOne();

        $this->assertInstanceOf(User::class, $result);

        $this->assertSame($u, $result);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $this->assertFalse($this->orm->getHeap()->has($result));
    }

    public function testHeap(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($this->orm->getHeap()->has($result));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($result)->getStatus());

        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'protected' => 12,
                'private' => 13,
            ],
            $this->orm->getHeap()->get($result)->getData()
        );
    }

    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->setProtectedRelation([]);
        $e->setPrivateRelation([]);

        $this->captureWriteQueries();

        $this->save($e);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());
    }

    public function testStoreWithUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->setProtectedRelation([]);
        $e->setPrivateRelation([]);

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($e);

        $e->balance = 400;

        $tr->run();

        $this->assertNumWrites(1);

        $this->assertEquals(3, $e->id);
        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }

    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $u->email = 'test@email.com';
        $this->assertSame('test@email.com', $u->email);

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u2->email);

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u3->email);

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);

        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)->findByPK(1);
        $this->assertSame('hello@world.com', $u4->email);
    }

    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertEquals(100.0, (float) $u->balance);
        $u->balance = 0.0;

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertEquals(0.0, (float) $u->balance);

        $u->balance = null;

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertNull($u->balance);
    }
}
