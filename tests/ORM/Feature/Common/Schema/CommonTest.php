<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserWithUUIDPrimaryKey;
use Cycle\ORM\Tests\Fixtures\UuidPrimaryKey;
use Cycle\ORM\Transaction;
use Ramsey\Uuid\Uuid;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class CommonTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::same($result[0]->id, 1);
        \Testo\Assert::same($result[0]->email, 'hello@world.com');
        \Testo\Assert::same($result[0]->balance, 100.0);
        \Testo\Assert::same($result[0]->active, true);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::same($result[1]->id, 2);
        \Testo\Assert::same($result[1]->email, 'another@world.com');
        \Testo\Assert::same($result[1]->balance, 200.0);
        \Testo\Assert::same($result[1]->active, false);
    }

    #[\Testo\Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[\Testo\Test]
    public function testMakeByRole(): void
    {
        \Testo\Assert::instanceOf($this->orm->make('user'), User::class);
    }

    #[\Testo\Test]
    public function testMakeByClass(): void
    {
        \Testo\Assert::instanceOf($this->orm->make(User::class), User::class);
    }

    #[\Testo\Test]
    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        \Testo\Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[\Testo\Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->wherePK(1)->fetchOne();

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::same($result->id, 1);
        \Testo\Assert::same($result->email, 'hello@world.com');
        \Testo\Assert::same($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testFetchByStringablePK(): void
    {
        $uuid = Uuid::uuid4();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid->toString()), 'uuid@world.com', 500);

        $this->save($e);

        $selector = new Select($this->orm, UserWithUUIDPrimaryKey::class);

        $result = $selector->wherePK($uuid)->fetchOne();

        \Testo\Assert::instanceOf($result, UserWithUUIDPrimaryKey::class);
        \Testo\Assert::same($result->getID()->getId(), $uuid->toString());
        \Testo\Assert::same($result->getEmail(), 'uuid@world.com');
    }

    #[\Testo\Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::same($result->id, 2);
        \Testo\Assert::same($result->email, 'another@world.com');
        \Testo\Assert::same($result->balance, 200.0);
    }

    #[\Testo\Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        \Testo\Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        \Testo\Assert::null($selector->where('id', 2)->fetchOne());

        \Testo\Assert::false($this->orm->getHeap()->has($result));
    }

    #[\Testo\Test]
    public function testHeap(): void
    {
        $result = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::true($this->orm->getHeap()->has($result));
        \Testo\Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);
        \Testo\Assert::same($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'active' => true,
            'balance' => 100.0,
        ]);
    }

    #[\Testo\Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->active = true;
        QueryAssert::writes(1, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });
        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        \Testo\Assert::same($e->id, 3);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[\Testo\Test]
    public function testStoreWithUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->active = true;
        QueryAssert::writes(1, function () use ($e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $e->balance = 400;
            $tr->run();
        });

        \Testo\Assert::same($e->id, 3);
        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        \Testo\Assert::equals($result->balance, 400);
        \Testo\Assert::same($result->active, true);
    }

    #[\Testo\Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::same($result[0]->id, 1);
        \Testo\Assert::same($result[0]->email, 'hello@world.com');
        \Testo\Assert::same($result[0]->balance, 100.0);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::same($result[1]->id, 2);
        \Testo\Assert::same($result[1]->email, 'another@world.com');
        \Testo\Assert::same($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::same($result->id, 1);
        \Testo\Assert::same($result->email, 'hello@world.com');
        \Testo\Assert::same($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::same($result->id, 2);
        \Testo\Assert::same($result->email, 'another@world.com');
        \Testo\Assert::same($result->balance, 200.0);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'active' => 'bool',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable(
            'user_with_uuid_primary_key',
            [
                'uuid' => 'string(36),primary',
                'email' => 'string',
                'balance' => 'float',
            ],
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'active', 'balance'],
            [
                ['hello@world.com', true, 100],
                ['another@world.com', false, 200],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'active', 'balance'],
                SchemaInterface::TYPECAST => [
                    'id' => 'int',
                    'active' => 'bool',
                    'balance' => 'float',
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
                SchemaInterface::SCOPE => SortByIDScope::class,
            ],
            UserWithUUIDPrimaryKey::class => [
                SchemaInterface::ROLE => 'user_with_uuid_primary_key',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user_with_uuid_primary_key',
                SchemaInterface::PRIMARY_KEY => 'uuid',
                SchemaInterface::COLUMNS => ['uuid', 'email', 'balance'],
                SchemaInterface::TYPECAST => [
                    'uuid' => [UuidPrimaryKey::class, 'typecast'],
                ],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }
}
