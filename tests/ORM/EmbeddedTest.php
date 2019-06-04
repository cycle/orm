<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class EmbeddedTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'            => 'primary',
            'email'         => 'string',
            'balance'       => 'float',
            'user_username' => 'string',
            'user_password' => 'string'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance', 'user_username', 'user_password'],
            [
                ['hello@world.com', 100, 'user1', 'pass1'],
                ['another@world.com', 200, 'user2', 'pass2'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class            => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'credentials' => [
                        Relation::TYPE   => Relation::EMBEDDED,
                        Relation::TARGET => UserCredentials::class,
                        Relation::LOAD   => null,
                        Relation::SCHEMA => [
                            //Relation::CASCADE => true,
                        ],
                    ]
                ]
            ],
            UserCredentials::class => [
                Schema::ROLE      => 'user_credentials',
                Schema::MAPPER    => Mapper::class,
                Schema::COLUMNS   => [
                    'username' => 'user_username',
                    'password' => 'user_password'
                ],
                Schema::SCHEMA    => [],
                Schema::RELATIONS => []
            ]
        ]));
    }

    public function testLoadDataNoRelation()
    {
        $selector = new Select($this->orm, User::class);

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
            ]
        ], $selector->fetchData());
    }

    public function testLoadDataLoadRelation()
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        $this->assertEquals([
            [
                'id'          => 1,
                'email'       => 'hello@world.com',
                'balance'     => 100.0,
                'credentials' => [
                    'username' => 'user1',
                    'password' => 'pass1'
                ]
            ],
            [
                'id'          => 2,
                'email'       => 'another@world.com',
                'balance'     => 200.0,
                'credentials' => [
                    'username' => 'user2',
                    'password' => 'pass2'
                ]
            ]
        ], $selector->fetchData());
    }
}