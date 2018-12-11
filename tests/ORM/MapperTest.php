<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class MapperTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchData()
    {
        $selector = new Selector($this->orm, User::class);

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
            ]
        ], $selector->fetchData());
    }

    public function testFetchAll()
    {
        $selector = new Selector($this->orm, User::class);
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

    public function testFetchOne()
    {
        $selector = new Selector($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testWhere()
    {
        $selector = new Selector($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testDelete()
    {
        $selector = new Selector($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $selector = new Selector($this->orm, User::class);
        $this->assertNull($selector->where('id', 2)->fetchOne());

        $this->assertFalse($this->orm->getHeap()->has($result));
    }

    public function testHeap()
    {
        $selector = new Selector($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($this->orm->getHeap()->has($result));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($result)->getStatus());

        $this->assertEquals(
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
            ],
            $this->orm->getHeap()->get($result)->getData()
        );
    }

    public function testStore()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();
        $this->assertNumWrites(0);

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());
    }

    public function testStoreWithUpdate()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $this->captureWriteQueries();

        $tr = new Transaction($this->orm);
        $tr->store($e);

        $e->balance = 400;

        $tr->run();

        $this->assertNumWrites(1);

        $this->assertEquals(3, $e->id);
        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $selector = new Selector($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }

    public function testRepositoryFindAll()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
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

    public function testRepositoryFindOne()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findOne();

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testRepositoryFindOneWithWhere()
    {
        $r = $this->orm->getMapper(User::class)->getRepository();
        $result = $r->findOne(['id' => 2]);

        $this->assertInstanceOf(User::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }
}