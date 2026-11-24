<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Exception\StatementException\ConstrainException;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\EntityManager;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\Tuple;
use Cycle\Testing\ORM\Memory;
use Cycle\Testing\ORM\OrmFactory;
use Testo\Assert;
use Testo\Expect;
use Testo\Test;

/**
 * Units of work over an in-memory SQLite database.
 */
#[Test]
final class TransactionTest
{
    public function emptyTransactionRunsNothing(): void
    {
        $orm = $this->orm();

        (new Transaction($orm))->run();

        Assert::same($this->db($orm)->getDriver()->getTransactionLevel(), 0);
        Assert::same($this->db($orm)->table('user')->count(), 0);
    }

    public function persistedEntityIsStored(): void
    {
        $orm = $this->orm();
        $user = $this->user('hello@world.com');

        (new Transaction($orm))->persist($user)->run();

        Assert::same($user->id, 1);
        Assert::same($this->db($orm)->table('user')->select('email')->fetchAll(), [['email' => 'hello@world.com']]);
    }

    public function deletedEntityIsRemoved(): void
    {
        $orm = $this->orm();
        $user = $this->user('hello@world.com');
        (new Transaction($orm))->persist($user)->run();

        (new Transaction($orm))->delete($user)->run();

        Assert::same($this->db($orm)->table('user')->count(), 0);
    }

    public function failedRunRethrowsTheError(): never
    {
        $orm = $this->orm();
        $this->db($orm)->table('user')->insertOne(['id' => 1, 'email' => 'first@world.com']);
        $user = $this->user('second@world.com');
        $user->id = 1;

        Expect::exception(ConstrainException::class);

        (new Transaction($orm))->persist($user)->run();
    }

    public function entityWithoutCommandIsNotStored(): void
    {
        $generator = new class extends CommandGenerator {
            protected function storeEntity(ORMInterface $orm, Tuple $tuple, bool $isNew): ?CommandInterface
            {
                return null;
            }
        };
        $orm = $this->orm(new OrmFactory($generator));
        $user = $this->user('hello@world.com');

        $state = (new EntityManager($orm))->persist($user)->run();

        Assert::true($state->isSuccess());
        Assert::null($user->id);
        Assert::same($this->db($orm)->table('user')->count(), 0);
    }

    private function orm(?OrmFactory $factory = null): ORMInterface
    {
        $orm = Memory::orm(new Schema([
            'user' => [
                Schema::ENTITY => User::class,
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]), factory: $factory);

        $table = $this->db($orm)->table('user')->getSchema();
        $table->primary('id');
        $table->string('email');
        $table->float('balance')->nullable();
        $table->save();

        return $orm;
    }

    private function db(ORMInterface $orm): DatabaseInterface
    {
        return $orm->getSource('user')->getDatabase();
    }

    private function user(string $email): User
    {
        $user = new User();
        $user->email = $email;
        return $user;
    }
}
