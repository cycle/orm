<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ORMTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

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
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testORMGet(): void
    {
        $this->assertNull($this->orm->get(User::class, ['id' => 1], false));
        $this->assertInstanceOf(User::class, $this->orm->get(User::class, ['id' => 1], true));

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $this->orm->get(User::class, ['id' => 1]));
        $this->assertNumReads(0);

        $this->assertCount(1, $this->orm->getSchema()->getRoles());
    }

    public function testORMClone(): void
    {
        $orm = $this->orm->withFactory($this->orm->getFactory());
        $this->assertNotSame($orm, $this->orm);
    }

    public function testORMGetByRole(): void
    {
        $this->assertNull($this->orm->get('user', ['id' => 1], false));
        $this->assertInstanceOf(User::class, $this->orm->get('user', ['id' => 1], true));

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $this->orm->get('user', ['id' => 1]));
        $this->assertNumReads(0);
    }
}
