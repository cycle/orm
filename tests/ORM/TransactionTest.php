<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
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

        $this->logger->display();

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
}

class TransactionTestMapper extends Mapper
{
    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        if ($entity->id == '3') {
            return new class implements CommandInterface {

                public function isReady(): bool
                {
                    return true;
                }

                public function isExecuted(): bool
                {
                    return false;
                }

                public function execute()
                {
                    throw new \Exception('Something went wrong');
                }

                public function complete()
                {
                }

                public function rollBack()
                {
                }
            };
        }

        return parent::queueDelete($entity, $node, $state);
    }
}
