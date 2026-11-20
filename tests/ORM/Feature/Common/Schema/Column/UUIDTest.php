<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema\Column;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\Uuid;
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class UUIDTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testCreate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(0);

        \Testo\Assert::equals($e->id, 1);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result = $selector->fetchOne();

        \Testo\Assert::instanceOf($result->uuid, Uuid::class);
        \Testo\Assert::equals($result->uuid->toString(), $e->uuid->toString());
    }

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $this->save($e);

        \Testo\Assert::equals($e->id, 1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchData();

        \Testo\Assert::instanceOf($result[0]['uuid'], Uuid::class);
        \Testo\Assert::equals($result[0]['uuid']->toString(), $e->uuid->toString());
    }

    #[\Testo\Test]
    public function testUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        \Testo\Assert::equals($e->id, 1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        \Testo\Assert::instanceOf($result->uuid, Uuid::class);
        \Testo\Assert::equals($result->uuid->toString(), $e->uuid->toString());

        $result->uuid = Uuid::create();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result2 = $selector->fetchOne();

        \Testo\Assert::instanceOf($result2->uuid, Uuid::class);
        \Testo\Assert::equals($result2->uuid->toString(), $result->uuid->toString());
    }

    #[\Testo\Lifecycle\BeforeTest]
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
}
