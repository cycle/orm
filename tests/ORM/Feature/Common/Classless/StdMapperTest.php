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
use Cycle\Testing\Database\Assert\QueryAssert;

class StdMapperTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, 'user');

        \Testo\Assert::equals($selector->fetchData(), [
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

    #[\Testo\Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchAll();

        \Testo\Assert::instanceOf($result[0], \stdClass::class);
        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::instanceOf($result[1], \stdClass::class);
        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::instanceOf($result, \stdClass::class);
        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::equals($result->email, 'hello@world.com');
        \Testo\Assert::equals($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[\Testo\Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        \Testo\Assert::equals($result->id, 2);
        \Testo\Assert::equals($result->email, 'another@world.com');
        \Testo\Assert::equals($result->balance, 200.0);
    }

    #[\Testo\Test]
    public function testDelete(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        $tr = new Transaction($this->orm);
        $tr->delete($result);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        \Testo\Assert::null($selector->where('id', 2)->fetchOne());

        $selector = new Select($this->orm, 'user');
        \Testo\Assert::null($selector->where('id', 2)->fetchOne());

        \Testo\Assert::false($this->orm->getHeap()->has($result));
    }

    #[\Testo\Test]
    public function testHeap(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::equals($result->id, 1);

        \Testo\Assert::true($this->orm->getHeap()->has($result));
        \Testo\Assert::same($this->orm->getHeap()->get($result)->getStatus(), Node::MANAGED);

        \Testo\Assert::equals($this->orm->getHeap()->get($result)->getData(), [
            'id' => 1,
            'email' => 'hello@world.com',
            'balance' => 100.0,
        ]);
    }

    #[\Testo\Test]
    public function testStore(): void
    {
        $e = $this->orm->make('user');
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::NEW);
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getRole(), 'user');

        $e->email = 'test@email.com';
        $e->balance = 300;
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

        \Testo\Assert::equals($e->id, 3);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[\Testo\Test]
    public function testStoreWithUpdate(): void
    {
        $e = $this->orm->make('user');
        $e->email = 'test@email.com';
        $e->balance = 300;
        QueryAssert::writes(1, function () use ($e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $e->balance = 400;
            $tr->run();
        });

        \Testo\Assert::equals($e->id, 3);
        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        $selector = new Select($this->orm->withHeap(new Heap()), 'user');
        $result = $selector->where('id', 3)->fetchOne();
        \Testo\Assert::equals($result->balance, 400);
    }

    #[\Testo\Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findAll();

        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne();

        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::equals($result->email, 'hello@world.com');
        \Testo\Assert::equals($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne(['id' => 2]);

        \Testo\Assert::equals($result->id, 2);
        \Testo\Assert::equals($result->email, 'another@world.com');
        \Testo\Assert::equals($result->balance, 200.0);
    }

    #[\Testo\Lifecycle\BeforeTest]
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
