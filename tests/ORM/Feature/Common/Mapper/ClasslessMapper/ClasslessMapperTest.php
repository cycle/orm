<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Mapper\ClasslessMapper;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\ClasslessMapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;

class ClasslessMapperTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchData(): void
    {
        \Testo\Assert::equals((new Select($this->orm, 'user'))->fetchData(), [
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
    public function testAssertRole(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[\Testo\Test]
    public function testMakeByRole(): void
    {
        $user = $this->orm->make('user');
        \Testo\Assert::object($user);
        \Testo\Assert::same($this->orm->resolveRole($user), 'user');
    }

    #[\Testo\Test]
    public function testAssertRoleViaClass(): void
    {
        $result = (new Select($this->orm, 'user'))->fetchOne();

        \Testo\Assert::same($this->orm->getHeap()->get($result)->getRole(), 'user');
    }

    #[\Testo\Test]
    public function testFetchAll(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchAll();

        \Testo\Assert::object($result[0]);
        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::object($result[1]);
        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testFetchOne(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();

        \Testo\Assert::object($result);
        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::equals($result->email, 'hello@world.com');
        \Testo\Assert::equals($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testFetchSame(): void
    {
        $u = $this->orm->make('user');
        $u->email = 'test';
        $u->balance = 100;

        $this->save($u);

        $result = (new Select($this->orm, 'user'))
            ->orderBy('id', 'DESC')
            ->fetchOne();

        \Testo\Assert::object($result);
        \Testo\Assert::equals($result->id, $u->id);
        \Testo\Assert::equals($result->email, $u->email);
        \Testo\Assert::equals($result->balance, $u->balance);

        \Testo\Assert::same($result, $u);
    }

    #[\Testo\Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->fetchOne();
        QueryAssert::writes(0, function () use ($result): void {
            $tr = new Transaction($this->orm);
            $tr->persist($result);
            $tr->run();
        });
    }

    #[\Testo\Test]
    public function testWhere(): void
    {
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 2)->fetchOne();

        \Testo\Assert::object($result);
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
        $result = (new Select($this->orm, 'user'))->fetchOne();

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

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, 'user');
        $result = $selector->where('id', 3)->fetchOne();
        \Testo\Assert::equals($result->balance, 400);
    }

    #[\Testo\Test]
    public function testRepositoryFindAll(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findAll();

        \Testo\Assert::object($result[0]);
        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::object($result[1]);
        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOne(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne();

        \Testo\Assert::object($result);
        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::equals($result->email, 'hello@world.com');
        \Testo\Assert::equals($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testRepositoryFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository('user');
        $result = $r->findOne(['id' => 2]);

        \Testo\Assert::object($result);
        \Testo\Assert::equals($result->id, 2);
        \Testo\Assert::equals($result->email, 'another@world.com');
        \Testo\Assert::equals($result->balance, 200.0);
    }

    #[\Testo\Test]
    public function testLoadOverwriteValues(): void
    {
        $u = $this->orm->getRepository('user')->findByPK(1);
        \Testo\Assert::same($u->email, 'hello@world.com');
        $u->email = 'test@email.com';
        \Testo\Assert::same($u->email, 'test@email.com');

        $u2 = $this->orm->getRepository('user')->findByPK(1);
        \Testo\Assert::same($u2, $u);
        \Testo\Assert::same($u2->email, 'test@email.com');

        $u3 = $this->orm->withHeap(new Heap())->getRepository('user')->findByPK(1);
        \Testo\Assert::same($u3->email, 'hello@world.com');
        QueryAssert::writes(1, function () use ($u): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->run();
        });

        $u4 = $this->orm->withHeap(new Heap())->getRepository('user')->findByPK(1);
        \Testo\Assert::same($u4->email, 'test@email.com');
    }

    #[\Testo\Test]
    public function testNullableValuesInASndOut(): void
    {
        $u = $this->orm->getRepository('user')->findByPK(1);
        \Testo\Assert::equals((float) $u->balance, 100.0);
        $u->balance = 0.0;
        QueryAssert::writes(1, function () use (&$t, $u): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository('user')->findByPK(1);
        \Testo\Assert::equals((float) $u->balance, 0.0);

        $u->balance = null;
        QueryAssert::writes(1, function () use (&$t, $u): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->run();
        });

        $this->orm = $this->orm->withHeap(new Heap());
        $u = $this->orm->getRepository('user')->findByPK(1);
        \Testo\Assert::null($u->balance);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float,nullable',
            ],
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    'user' => [
                        Schema::MAPPER => ClasslessMapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'email', 'balance'],
                        Schema::TYPECAST => ['balance' => 'float'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ],
            ),
        );
    }
}
