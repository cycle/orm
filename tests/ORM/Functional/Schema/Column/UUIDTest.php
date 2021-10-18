<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Schema\Column;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Functional\BaseTest;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class UUIDTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'uuid' => 'binary(16)',
            'email' => 'string',
            'balance' => 'float',
        ], [], null, ['uuid' => null]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'uuid', 'email', 'balance'],
                Schema::TYPECAST => [
                    'id' => 'int',
                    'uuid' => [Uuid::class, 'parse'],
                    'balance' => 'float',
                ],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testCreate(): void
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

    public function testFetchData(): void
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

    public function testUpdate(): void
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
