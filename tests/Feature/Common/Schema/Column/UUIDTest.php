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
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class UUIDTest extends OrmTestCase
{
    #[Test]
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

        Assert::equals($e->id, 1);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result = $selector->fetchOne();

        Assert::instanceOf($result->uuid, Uuid::class);
        Assert::equals($result->uuid->toString(), $e->uuid->toString());
    }

    #[Test]
    public function testFetchData(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $this->save($e);

        Assert::equals($e->id, 1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchData();

        Assert::instanceOf($result[0]['uuid'], Uuid::class);
        Assert::equals($result[0]['uuid']->toString(), $e->uuid->toString());
    }

    #[Test]
    public function testUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->uuid = Uuid::create();
        $e->balance = 300;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        Assert::equals($e->id, 1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::instanceOf($result->uuid, Uuid::class);
        Assert::equals($result->uuid->toString(), $e->uuid->toString());

        $result->uuid = Uuid::create();

        $tr = new Transaction($this->orm);
        $tr->persist($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $result2 = $selector->fetchOne();

        Assert::instanceOf($result2->uuid, Uuid::class);
        Assert::equals($result2->uuid->toString(), $result->uuid->toString());
    }

    #[BeforeTest]
    public function setUp(): void
    {
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
