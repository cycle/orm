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
use Cycle\Testing\ORM\OrmTestCase;
use Ramsey\Uuid\Uuid;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class CommonTest extends OrmTestCase
{
    #[Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::same($result[0]->id, 1);
        Assert::same($result[0]->email, 'hello@world.com');
        Assert::same($result[0]->balance, 100.0);
        Assert::same($result[0]->active, true);

        Assert::instanceOf($result[1], User::class);
        Assert::same($result[1]->id, 2);
        Assert::same($result[1]->email, 'another@world.com');
        Assert::same($result[1]->balance, 200.0);
        Assert::same($result[1]->active, false);
    }

    #[Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testMakeByRole(): void
    {
        Assert::instanceOf($this->orm->make('user'), User::class);
    }

    #[Test]
    public function testMakeByClass(): void
    {
        Assert::instanceOf($this->orm->make(User::class), User::class);
    }

    #[Test]
    public function testAssertRoleViaClass(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->wherePK(1)->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::same($result->id, 1);
        Assert::same($result->email, 'hello@world.com');
        Assert::same($result->balance, 100.0);
    }

    #[Test]
    public function testFetchByStringablePK(): void
    {
        $uuid = Uuid::uuid4();
        $e = new UserWithUUIDPrimaryKey(new UuidPrimaryKey($uuid->toString()), 'uuid@world.com', 500);

        $this->save($e);

        $selector = new Select($this->orm, UserWithUUIDPrimaryKey::class);

        $result = $selector->wherePK($uuid)->fetchOne();

        Assert::instanceOf($result, UserWithUUIDPrimaryKey::class);
        Assert::same($result->getID()->getId(), $uuid->toString());
        Assert::same($result->getEmail(), 'uuid@world.com');
    }

    #[Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        Assert::instanceOf($result, User::class);
        Assert::same($result->id, 2);
        Assert::same($result->email, 'another@world.com');
        Assert::same($result->balance, 200.0);
    }

    #[Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, User::class);
        Assert::null($selector->where('id', 2)->fetchOne());

        Assert::false($this->orm->getHeap()->has($result));
    }

    #[Test]
    public function testHeap(): void
    {
        $result = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->fetchOne();

        Assert::equals($result->id, 1);
        Assert::true($this->orm->getHeap()->has($result));
        Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'active' => true,
            'balance' => 100.0,
        ]);
    }

    #[Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->active = true;

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

        Assert::same($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[Test]
    public function testStoreWithUpdate(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->active = true;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $e->balance = 400;
        $tr->run();
        $this->assertNumWrites(1);

        Assert::same($e->id, 3);
        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $result = $selector->where('id', 3)->fetchOne();
        Assert::equals($result->balance, 400);
        Assert::same($result->active, true);
    }

    #[Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        Assert::instanceOf($result[0], User::class);
        Assert::same($result[0]->id, 1);
        Assert::same($result[0]->email, 'hello@world.com');
        Assert::same($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::same($result[1]->id, 2);
        Assert::same($result[1]->email, 'another@world.com');
        Assert::same($result[1]->balance, 200.0);
    }

    #[Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        Assert::instanceOf($result, User::class);
        Assert::same($result->id, 1);
        Assert::same($result->email, 'hello@world.com');
        Assert::same($result->balance, 100.0);
    }

    #[Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        Assert::instanceOf($result, User::class);
        Assert::same($result->id, 2);
        Assert::same($result->email, 'another@world.com');
        Assert::same($result->balance, 200.0);
    }

    #[BeforeTest]
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
