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
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Traits\TableTrait;

class DeepEmbeddedTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'group',
            [
                'id'   => 'primary',
                'name' => 'string'
            ]
        );

        $this->makeTable(
            'user',
            [
                'id'             => 'primary',
                'group_id'       => 'int',
                'email'          => 'string',
                'balance'        => 'float',
                'creds_username' => 'string',
                'creds_password' => 'string',
            ]
        );

        $this->getDatabase()->table('group')->insertMultiple(
            ['name'],
            [
                ['first'],
                ['second']
            ]
        );

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'group_id', 'balance', 'creds_username', 'creds_password'],
            [
                ['hello@world.com', 1, 100, 'user1', 'pass1'],
                ['another@world.com', 1, 200, 'user2', 'pass2'],
                ['third@world.com', 2, 200, 'user3', 'pass3'],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    Post::class            => [
                        Schema::ROLE        => 'post',
                        Schema::MAPPER      => Mapper::class,
                        Schema::DATABASE    => 'default',
                        Schema::TABLE       => 'post',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS     => ['id', 'email', 'balance'],
                        Schema::SCHEMA      => [],
                        Schema::RELATIONS   => [
                            'comments' => [
                                Relation::TYPE   => Relation::HAS_MANY,
                                Relation::TARGET => Comment::class,
                                Relation::SCHEMA => [
                                    Relation::CASCADE   => true,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user_id',
                                ],
                            ]
                        ]
                    ],
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
                                Relation::TARGET => 'user:credentials',
                                Relation::LOAD   => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [],
                            ],
                        ]
                    ],
                    UserCredentials::class => [
                        Schema::ROLE        => 'user:credentials',
                        Schema::MAPPER      => Mapper::class,
                        Schema::DATABASE    => 'default',
                        Schema::TABLE       => 'user',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS     => [
                            'id'       => 'id',
                            'username' => 'creds_username',
                            'password' => 'creds_password',
                        ],
                        Schema::SCHEMA      => [],
                        Schema::RELATIONS   => []
                    ]
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('credentials');

        dump($selector->fetchData());
    }
}
