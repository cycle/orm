<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests\Classless;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class ClasslessMapperTest extends BaseTest
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
            'user' => [
                Schema::MAPPER      => StdMapper::class,
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
        $selector = new Select($this->orm, 'user');

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
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(\stdClass::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertInstanceOf(\stdClass::class, $result[1]);
        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testFetchOne()
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testAssertRole()
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        $this->assertSame('user', $this->orm->getHeap()->get($result)->getRole());
    }

    public function testWhere()
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }

    public function testDelete()
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

    public function testHeap()
    {
        $selector = new Select($this->orm, 'user');
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
        $e = $this->orm->make('user');
        $this->assertSame(Node::NEW, $this->orm->getHeap()->get($e)->getStatus());
        $this->assertSame('user', $this->orm->getHeap()->get($e)->getRole());

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

    public function testStoreWithUpdate()
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

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        $result = $selector->where('id', 3)->fetchOne();
        $this->assertEquals(400, $result->balance);
    }

    public function testRepositoryFindAll()
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findAll();

        $this->assertEquals(1, $result[0]->id);
        $this->assertEquals('hello@world.com', $result[0]->email);
        $this->assertEquals(100.0, $result[0]->balance);

        $this->assertEquals(2, $result[1]->id);
        $this->assertEquals('another@world.com', $result[1]->email);
        $this->assertEquals(200.0, $result[1]->balance);
    }

    public function testRepositoryFindOne()
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne();

        $this->assertEquals(1, $result->id);
        $this->assertEquals('hello@world.com', $result->email);
        $this->assertEquals(100.0, $result->balance);
    }

    public function testRepositoryFindOneWithWhere()
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne(['id' => 2]);

        $this->assertEquals(2, $result->id);
        $this->assertEquals('another@world.com', $result->email);
        $this->assertEquals(200.0, $result->balance);
    }
}