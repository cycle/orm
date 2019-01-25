<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Fixtures\Uuid;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

abstract class UUIDColumnTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'uuid'    => 'binary(16)',
            'email'   => 'string',
            'balance' => 'float'
        ], [], null, ['uuid' => null]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'uuid', 'email', 'balance'],
                Schema::TYPECAST    => [
                    'id'      => 'int',
                    'uuid'    => [Uuid::class, 'parse'],
                    'balance' => 'float'
                ],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testCreate()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
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

        $this->assertEquals(1, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(Uuid::class, $result->uuid);
        $this->assertEquals($e->uuid->toString(), $result->uuid->toString());
    }

    public function testFetchData()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertEquals(1, $e->id);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchData();

        $this->assertInstanceOf(Uuid::class, $result[0]['uuid']);
        $this->assertEquals($e->uuid->toString(), $result[0]['uuid']->toString());
    }

    public function testUpdate()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertEquals(1, $e->id);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        $this->assertInstanceOf(Uuid::class, $result->uuid);
        $this->assertEquals($e->uuid->toString(), $result->uuid->toString());

        $result->uuid = Uuid::create();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result2 = $selector->fetchOne();

        $this->assertInstanceOf(Uuid::class, $result2->uuid);
        $this->assertEquals($result->uuid->toString(), $result2->uuid->toString());
    }
}