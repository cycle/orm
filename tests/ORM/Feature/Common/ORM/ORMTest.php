<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\ORM;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class ORMTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testORMGet(): void
    {
        \Testo\Assert::null($this->orm->get(User::class, ['id' => 1], false));
        \Testo\Assert::instanceOf($this->orm->get(User::class, ['id' => 1], true), User::class);
        QueryAssert::reads(0, fn() => \Testo\Assert::instanceOf($this->orm->get(User::class, ['id' => 1]), User::class));

        \Testo\Assert::count($this->orm->getSchema()->getRoles(), 1);
    }

    #[\Testo\Test]
    public function testORMGetByRole(): void
    {
        \Testo\Assert::null($this->orm->get('user', ['id' => 1], false));
        \Testo\Assert::instanceOf($this->orm->get('user', ['id' => 1], true), User::class);
        QueryAssert::reads(0, fn() => \Testo\Assert::instanceOf($this->orm->get('user', ['id' => 1]), User::class));
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
            User::class => [
                SchemaInterface::ROLE => 'user',
                SchemaInterface::MAPPER => Mapper::class,
                SchemaInterface::DATABASE => 'default',
                SchemaInterface::TABLE => 'user',
                SchemaInterface::PRIMARY_KEY => 'id',
                SchemaInterface::COLUMNS => ['id', 'email', 'balance'],
                SchemaInterface::SCHEMA => [],
                SchemaInterface::RELATIONS => [],
            ],
        ]));
    }
}
