<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\TransactionTestMapper;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class TransactionTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testTransactionRollbackShouldResetEntityState()
    {
        $t = new Transaction($this->orm);

        $s = new Select($this->orm, User::class);

        $u1 = $s->wherePK(1)->fetchOne();
        $u1->balance = 150;

        $s = new Select($this->orm, User::class);
        $u2 = $s->wherePK(2)->fetchOne();
        $u2->balance = 250;

        $s = new Select($this->orm, User::class);
        $u4 = $s->wherePK(3)->fetchOne();

        $u = new User();
        $u->email = 'foo@site.com';
        $u->balance = 300;

        $t->persist($u1);
        $t->delete($u2);
        $t->persist($u);
        $t->delete($u4);

        $t1 = clone $t;
        $t2 = clone $t1;

        try {
            $this->captureWriteQueries();
            $t->run();
        } catch (\Exception $e) {
            $this->assertNumWrites(3);
            \Testo\Assert::null($u->id);
        }

        try {
            $t1->run();
        } catch (\Exception $e) {
            $this->assertNumWrites(3);
            \Testo\Assert::null($u->id);
        }

        try {
            $t2->run();
        } catch (\Exception $e) {
            $this->assertNumWrites(3);
            \Testo\Assert::null($u->id);
        }

        $this->orm->getHeap()->clean();
    }

    #[\Testo\Test]
    public function testRollbackDatabaseTransactionAfterRunORMTransaction()
    {
        $dbal = $this->orm->getFactory()->database();

        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();
        $u->balance = 150;

        $u3 = (new Select($this->orm, User::class))->wherePK(2)->fetchOne();

        $newU = new User();
        $newU->email = 'foo@site.com';
        $newU->balance = 300;

        $dbal->begin();

        try {
            $t = new Transaction($this->orm);

            $t->persist($u);
            $t->persist($newU);

            $t->run();

            $t->delete($u3);

            $newU->balance = 350;
            $t->persist($newU);

            $t->run();

            throw new \Exception('Something went wrong outside transaction');

            $dbal->commit();
        } catch (\Throwable $e) {
            \Testo\Assert::same($e->getMessage(), 'Something went wrong outside transaction');
            $dbal->rollback();
        }

        $this->orm->getHeap()->clean();

        \Testo\Assert::same((new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance, 100);
        \Testo\Assert::notNull((new Select($this->orm, User::class))->wherePK(2)->fetchOne());
        \Testo\Assert::null((new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne());
    }

    #[\Testo\Test]
    public function testRollbackDatabaseTransactionDuringRunORMTransaction()
    {
        $dbal = $this->orm->getFactory()->database();

        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();
        $u->balance = 150;

        $u3 = (new Select($this->orm, User::class))->wherePK(3)->fetchOne();

        $newU = new User();
        $newU->email = 'foo@site.com';
        $newU->balance = 300;

        $dbal->begin();

        try {
            $t = new Transaction($this->orm);

            $t->persist($u);
            $t->persist($newU);

            $t->run();

            \Testo\Assert::same((new Select($this->orm->withHeap(new Heap()), User::class))->wherePK(1)->fetchOne()->balance, 150);

            // For user with ID 3 Mapper should throw an exception
            $t->delete($u3);

            $newU->balance = 350;
            $t->persist($newU);

            $t->run();

            \Testo\Assert::fail('Exception should be thrown.');

            $dbal->commit();
        } catch (\Throwable $e) {
            \Testo\Assert::same($e->getMessage(), 'Something went wrong');

            $dbal->rollback();
        }

        $this->orm->getHeap()->clean();

        \Testo\Assert::same((new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance, 100);
        \Testo\Assert::notNull((new Select($this->orm, User::class))->wherePK(3)->fetchOne());
        \Testo\Assert::null((new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne());
    }

    #[\Testo\Test]
    public function testCommitDatabaseTransactionAfterORMTransaction()
    {
        $dbal = $this->orm->getFactory()->database();

        $dbal->begin();

        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();
        $u->balance = 150;

        $u2 = (new Select($this->orm, User::class))->wherePK(2)->fetchOne();

        $newU = new User();
        $newU->email = 'foo@site.com';
        $newU->balance = 300;

        $t = new Transaction($this->orm);

        $t->persist($u);
        $t->persist($newU);

        $t->run();

        $t->delete($u2);

        $newU->balance = 350;
        $t->persist($newU);

        $t->run();

        $dbal->commit();

        $this->orm->getHeap()->clean();

        \Testo\Assert::same((new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance, 150);
        \Testo\Assert::null((new Select($this->orm, User::class))->wherePK(2)->fetchOne());
        \Testo\Assert::same((new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne()->balance, 350);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', ['id' => 'primary', 'email' => 'string', 'balance' => 'float',]);
        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
                ['test@world.com', 300],
            ],
        );

        $this->orm = $this->withSchema(
            new Schema([
                User::class => [
                    Schema::ROLE => 'user',
                    Schema::MAPPER => TransactionTestMapper::class,
                    Schema::DATABASE => 'default',
                    Schema::TABLE => 'user',
                    Schema::PRIMARY_KEY => 'id',
                    Schema::COLUMNS => ['id', 'email', 'balance'],
                    Schema::TYPECAST => ['id' => 'int', 'balance' => 'int'],
                    Schema::SCHEMA => [],
                    Schema::RELATIONS => [],
                ],
            ]),
        );
    }
}
