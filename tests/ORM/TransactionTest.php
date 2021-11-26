<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

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
            ]
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
            ])
        );
    }

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
            $this->assertNull($u->id);
        }

        try {
            $this->captureWriteQueries();
            $t1->run();
        } catch (\Exception $e) {
            $this->assertNumWrites(3);
            $this->assertNull($u->id);
        }

        try {
            $this->captureWriteQueries();
            $t2->run();
        } catch (\Exception $e) {
            $this->assertNumWrites(3);
            $this->assertNull($u->id);
        }

        $this->orm->getHeap()->clean();
    }

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
            $this->assertSame('Something went wrong outside transaction', $e->getMessage());
            $dbal->rollback();
        }

        $this->orm->getHeap()->clean();

        $this->assertSame(100, (new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance);
        $this->assertNotNull((new Select($this->orm, User::class))->wherePK(2)->fetchOne());
        $this->assertNull((new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne());
    }

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

            $this->assertSame(
                150,
                (new Select($this->orm->withHeap(new Heap()), User::class))->wherePK(1)->fetchOne()->balance
            );

            // For user with ID 3 Mapper should throw an exception
            $t->delete($u3);

            $newU->balance = 350;
            $t->persist($newU);

            $t->run();

            $this->fail('Exception should be thrown.');

            $dbal->commit();
        } catch (\Throwable $e) {
            $this->assertSame('Something went wrong', $e->getMessage());
            $dbal->rollback();
        }

        $this->orm->getHeap()->clean();

        $this->assertSame(100, (new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance);
        $this->assertNotNull((new Select($this->orm, User::class))->wherePK(3)->fetchOne());
        $this->assertNull((new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne());
    }

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

        $this->assertSame(150, (new Select($this->orm, User::class))->wherePK(1)->fetchOne()->balance);
        $this->assertNull((new Select($this->orm, User::class))->wherePK(2)->fetchOne());
        $this->assertSame(350, (new Select($this->orm, User::class))->wherePK($newU->id)->fetchOne()->balance);
    }
}
