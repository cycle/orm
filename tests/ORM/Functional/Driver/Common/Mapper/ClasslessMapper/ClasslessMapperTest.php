<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Mapper\ClasslessMapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\ClasslessMapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\BaseMapperTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class ClasslessMapperTest extends BaseMapperTest
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
            ]
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    'user' => [
                        Schema::MAPPER => ClasslessMapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'email', 'balance'],
                        Schema::TYPECAST => ['balance' => 'float'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
                [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            (new Select($this->orm, 'user'))->fetchData()
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
        $user = $this->orm->make('user');
        $this->assertIsObject($user);
        $this->assertSame('user', $this->orm->resolveRole($user));
    }

    public function testAssertRoleViaClass(): void
    {
        $result = (new Select($this->orm, 'user'))->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchAll();

        $this->assertIsObject($result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertIsObject($result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testFetchSame(): void
    {
        $u = $this->orm->make('user');
        $u->email = 'test';
        $u->balance = 100;

        $this->save($u);

        $selector = new Select($this->orm, 'user');
        $result = $selector->orderBy('id', 'DESC')->fetchOne();

        $this->assertIsObject($result);
        $this->assertEquals($u->id, $result->id);
        $this->assertEquals($u->email, $result->email);
        $this->assertEquals($u->balance, $result->balance);

        $this->assertSame($u, $result);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testWhere(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertIsObject($result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testDelete(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, 'user');
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $this->assertFalse($this->orm->getHeap()->has($result));
    }

    public function testHeap(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($this->orm->getHeap()->has($result));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($result)->getStatus());

        $this->assertEquals(
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
            ],
            $this->orm->getHeap()->get($result)->getData()
        );
    }

    public function testStore(): void
    {
        $e = $this->orm->make('user');
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());
    }

    public function testStoreWithUpdate(): void
    {
        $e = $this->orm->make('user');
        $e->email = 'test@email.com';
        $e->balance = 300;

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
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }

    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findAll();

        $this->assertIsObject($result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertIsObject($result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne();

        $this->assertIsObject($result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne(['id' => 2]);

        $this->assertIsObject($result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository('user')->findByPK(1);
        $u->email = 'test@email.com';
        $this->assertSame('test@email.com', $u->email);

        $u2 = $this->orm->getRepository('user')->findByPK(1);
        $this->assertSame('hello@world.com', $u2->email);

        $u3 = $this->orm->withHeap(new Heap())->getRepository('user')->findByPK(1);
        $this->assertSame('hello@world.com', $u3->email);

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(0);

        $u4 = $this->orm->withHeap(new Heap())->getRepository('user')->findByPK(1);
        $this->assertSame('hello@world.com', $u4->email);
    }

    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository('user')->findByPK(1);
        $this->assertEquals(100.0, (float) $u->balance);
        $u->balance = 0.0;

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository('user')->findByPK(1);
        $this->assertEquals(0.0, (float) $u->balance);

        $u->balance = null;

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository('user')->findByPK(1);
        $this->assertNull($u->balance);
    }
}
