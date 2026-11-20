<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Select\Repository;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserRepository;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class CustomRepositoryTest extends OrmTestCase
{
    #[Test]
    public function testFindAll(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
    }

    #[Test]
    public function testCloned(): void
    {
        $r = $this->orm->getRepository(User::class);

        /** @var Repository $r2 */
        $r2 = clone $r;

        Assert::notSame($r2->select(), $r->select());
    }

    #[Test]
    public function testFindByEmailOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findByEmail('hello@world.com');

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testFindOne(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne();

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 1);
        Assert::equals($result->email, 'hello@world.com');
        Assert::equals($result->balance, 100.0);
    }

    #[Test]
    public function testFindOneWithWhere(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 2]);

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testFindNull(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findOne(['id' => 3]);

        Assert::null($result);
    }

    #[Test]
    public function testFindByPK(): void
    {
        $r = $this->orm->getRepository(User::class);
        $result = $r->findByPK(2);

        Assert::instanceOf($result, User::class);
        Assert::equals($result->id, 2);
        Assert::equals($result->email, 'another@world.com');
        Assert::equals($result->balance, 200.0);
    }

    #[Test]
    public function testFindImmutable(): void
    {
        /** @var Repository $r */
        $r = $this->orm->getRepository(User::class);

        $result = $r->select()->orderBy('id', 'DESC')->fetchAll();

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 1);
        Assert::equals($result[1]->email, 'hello@world.com');
        Assert::equals($result[1]->balance, 100.0);

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 2);
        Assert::equals($result[0]->email, 'another@world.com');
        Assert::equals($result[0]->balance, 200.0);

        // immutable
        $result = $r->select()->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
        Assert::equals($result[0]->email, 'hello@world.com');
        Assert::equals($result[0]->balance, 100.0);

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->id, 2);
        Assert::equals($result[1]->email, 'another@world.com');
        Assert::equals($result[1]->balance, 200.0);
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
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::REPOSITORY => UserRepository::class,
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
