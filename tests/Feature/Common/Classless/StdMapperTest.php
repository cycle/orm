<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Classless;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\StdMapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class StdMapperTest extends OrmTestCase
{
    #[Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, 'user');

        Assert::equals($selector->fetchData(), [
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
        ]);
    }

    #[Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], \stdClass::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], \stdClass::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::instanceOf($result, \stdClass::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, 'user');
        Assert::null($selector->where('id', 2)->fetchOne());

        Assert::false($this->orm->getHeap()->has($result));
    }

    #[Test]
    public function testHeap(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        Assert::equals($result->id, 1);

        Assert::true($this->orm->getHeap()->has($result));
        Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);

        Assert::equals($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
        ]);
    }

    #[Test]
    public function testStore(): void
    {
        $e = $this->orm->make('user');
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::NEW);
        Assert::same($this->orm->getHeap()->get($e)->getRole(), 'user');

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

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[Test]
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

        Assert::equals($e->id, 3);
        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        $result = $selector->where('id', 3)->fetchOne();
        Assert::equals($result->balance, 400);
    }

    #[Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findAll();

        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne();

        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne(['id' => 2]);

        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            'user' => [
                Schema::MAPPER => StdMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
