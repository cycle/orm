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
use Cycle\ORM\Tests\Fixtures\Group;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserCredentials;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class DeepEmbeddedTest extends BaseTest
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
                    Group::class           => [
                        Schema::ROLE        => 'group',
                        Schema::MAPPER      => Mapper::class,
                        Schema::DATABASE    => 'default',
                        Schema::TABLE       => 'group',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS     => ['id', 'name'],
                        Schema::SCHEMA      => [],
                        Schema::TYPECAST    => ['id' => 'int'],
                        Schema::RELATIONS   => [
                            'users' => [
                                Relation::TYPE   => Relation::HAS_MANY,
                                Relation::TARGET => User::class,
                                Relation::SCHEMA => [
                                    Relation::CASCADE   => true,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'group_id',
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
                        Schema::COLUMNS     => ['id', 'group_id', 'email', 'balance'],
                        Schema::SCHEMA      => [],
                        Schema::TYPECAST    => ['id' => 'int', 'group_id' => 'int', 'balance' => 'float'],
                        Schema::RELATIONS   => [
                            'credentials' => [
                                Relation::TYPE   => Relation::EMBEDDED,
                                Relation::TARGET => 'user:credentials',
                                Relation::LOAD   => Relation::LOAD_EAGER, // IMPORTANT!
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
                        Schema::TYPECAST    => ['id' => 'int'],
                        Schema::RELATIONS   => []
                    ]
                ]
            )
        );
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, Group::class);
        $selector->load('users')->orderBy('id', 'ASC');

        $this->assertSame(
            [
                [
                    'id'    => 1,
                    'name'  => 'first',
                    'users' => [
                        [
                            'id'          => 1,
                            'group_id'    => 1,
                            'email'       => 'hello@world.com',
                            'balance'     => 100.0,
                            'credentials' => [
                                'username' => 'user1',
                                'password' => 'pass1',
                            ],
                        ],
                        [
                            'id'          => 2,
                            'group_id'    => 1,
                            'email'       => 'another@world.com',
                            'balance'     => 200.0,
                            'credentials' => [
                                'username' => 'user2',
                                'password' => 'pass2',
                            ],
                        ],
                    ],
                ],
                [
                    'id'    => 2,
                    'name'  => 'second',
                    'users' => [
                        [
                            'id'          => 3,
                            'group_id'    => 2,
                            'email'       => 'third@world.com',
                            'balance'     => 200.0,
                            'credentials' => [
                                'username' => 'user3',
                                'password' => 'pass3',
                            ],
                        ],
                    ],
                ],
            ],
            $selector->fetchData()
        );
    }

    public function testWithRelationIgnoreEager(): void
    {
        $selector = new Select($this->orm, Group::class);
        $selector->with('users')->orderBy('id', 'ASC');

        $this->assertSame(
            [
                [
                    'id'   => 1,
                    'name' => 'first',
                ],
                [
                    'id'   => 2,
                    'name' => 'second',
                ],
            ],
            $selector->fetchData()
        );

        // only parent entity!
        $this->assertCount(2, $selector->buildQuery()->getColumns());
    }

    public function testFetchDataViaJoin(): void
    {
        $selector = new Select($this->orm, Group::class);
        $selector->with('users', ['as' => 'users'])
            ->load('users', ['using' => 'users'])
            ->orderBy('id', 'ASC');

        $this->assertSame(
            [
                [
                    'id'    => 1,
                    'name'  => 'first',
                    'users' => [
                        [
                            'id'          => 1,
                            'group_id'    => 1,
                            'email'       => 'hello@world.com',
                            'balance'     => 100.0,
                            'credentials' => [
                                'username' => 'user1',
                                'password' => 'pass1',
                            ],
                        ],
                        [
                            'id'          => 2,
                            'group_id'    => 1,
                            'email'       => 'another@world.com',
                            'balance'     => 200.0,
                            'credentials' => [
                                'username' => 'user2',
                                'password' => 'pass2',
                            ],
                        ],
                    ],
                ],
                [
                    'id'    => 2,
                    'name'  => 'second',
                    'users' => [
                        [
                            'id'          => 3,
                            'group_id'    => 2,
                            'email'       => 'third@world.com',
                            'balance'     => 200.0,
                            'credentials' => [
                                'username' => 'user3',
                                'password' => 'pass3',
                            ],
                        ],
                    ],
                ],
            ],
            $selector->fetchData()
        );

        $this->assertCount(8, $selector->buildQuery()->getColumns());
    }
}
