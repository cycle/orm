<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Select;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Schema;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserPersistRepository;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class PersistRepositoryTest extends BaseTest
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
                Schema::REPOSITORY => UserPersistRepository::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::RELATIONS => [],
            ],
        ]));
    }

    public function testPersist(): void
    {
        /** @var UserPersistRepository $users */
        $users = $this->orm->getRepository(User::class);


        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 1000;

        $this->assertNull($u->id);

        $users->save($u);

        $this->assertNotNull($u->id);
    }
}
