<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Schema\Column;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class ColumnAliasesTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testStore(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        QueryAssert::writes(1, function () use ($e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        \Testo\Assert::equals($e->id, 3);

        \Testo\Assert::true($this->orm->getHeap()->has($e));
        \Testo\Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);
    }

    #[\Testo\Test]
    public function testFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Test]
    public function testCloned(): void
    {
        $r = $this->orm->getRepository(User::class);

        /** @var Repository $r2 */
        $r2 = clone $r;

        \Testo\Assert::notSame($r2->select(), $r->select());
    }

    #[\Testo\Test]
    public function testFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::equals($result->id, 1);
        \Testo\Assert::equals($result->email, 'hello@world.com');
        \Testo\Assert::equals($result->balance, 100.0);
    }

    #[\Testo\Test]
    public function testFindDirectOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id_int' => 2]);

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::equals($result->id, 2);
        \Testo\Assert::equals($result->email, 'another@world.com');
        \Testo\Assert::equals($result->balance, 200.0);
    }

    #[\Testo\Test]
    public function testFindDirectNull(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id_int' => 3]);

        \Testo\Assert::null($result);
    }

    #[\Testo\Test]
    public function testFindDirectByPK(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findByPK(2);

        \Testo\Assert::instanceOf($result, User::class);
        \Testo\Assert::equals($result->id, 2);
        \Testo\Assert::equals($result->email, 'another@world.com');
        \Testo\Assert::equals($result->balance, 200.0);
    }

    #[\Testo\Test]
    public function testFindDirectImmutable(): void
    {
        /** @var Repository $r */
        $r = $this->orm->getRepository(User::class);

        $result = $r->select()->orderBy('id_int', 'DESC')->fetchAll();

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::equals($result[1]->id, 1);
        \Testo\Assert::equals($result[1]->email, 'hello@world.com');
        \Testo\Assert::equals($result[1]->balance, 100.0);

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::equals($result[0]->id, 2);
        \Testo\Assert::equals($result[0]->email, 'another@world.com');
        \Testo\Assert::equals($result[0]->balance, 200.0);

        // immutable
        $result = $r->select()->fetchAll();

        \Testo\Assert::instanceOf($result[0], User::class);
        \Testo\Assert::equals($result[0]->id, 1);
        \Testo\Assert::equals($result[0]->email, 'hello@world.com');
        \Testo\Assert::equals($result[0]->balance, 100.0);

        \Testo\Assert::instanceOf($result[1], User::class);
        \Testo\Assert::equals($result[1]->id, 2);
        \Testo\Assert::equals($result[1]->email, 'another@world.com');
        \Testo\Assert::equals($result[1]->balance, 200.0);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
