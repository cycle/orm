<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\State;
use Spiral\ORM\Tests\Fixtures\Mapper\UserEntity;
use Spiral\ORM\Tests\Fixtures\Mapper\UserMapper;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

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

        $this->orm = $this->orm->withSchema(new Schema([
            UserEntity::class => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => UserMapper::class,
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
        $selector = new Selector($this->orm, UserEntity::class);

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
        $selector = new Selector($this->orm, UserEntity::class);
        $result = $selector->fetchAll();

        $this->assertInstanceOf(UserEntity::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(UserEntity::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testFetchOne()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(UserEntity::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testWhere()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertInstanceOf(UserEntity::class, $result);
        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testHeap()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $result = $selector->fetchOne();

        $this->assertEquals(1, $result->id);

        $this->assertTrue($this->orm->getHeap()->has($result));
        $this->assertSame(State::LOADED, $this->orm->getHeap()->get($result)->getState());

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
        $e = new UserEntity();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(State::LOADED, $this->orm->getHeap()->get($e)->getState());
    }

    public function testStoreWithUpdate()
    {
        $e = new UserEntity();
        $e->email = 'test@email.com';
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->store($e);

        $e->balance = 400;
        $tr->store($e);

        $tr->run();

        $this->assertEquals(3, $e->id);
        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(State::LOADED, $this->orm->getHeap()->get($e)->getState());

        $selector = new Selector($this->orm, UserEntity::class);
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }
}