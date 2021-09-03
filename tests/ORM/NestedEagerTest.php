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
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class NestedEagerTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable(
            'user',
            [
                'id' => 'primary',
                'email' => 'string',
                'balance' => 'float',
            ]
        );

        $this->makeTable(
            'profile',
            [
                'id' => 'primary',
                'user_id' => 'integer,nullable',
                'image' => 'string',
                'label' => 'string',
            ]
        );

        $this->makeFK('profile', 'user_id', 'user', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
                ['third@world.com', 150],
            ]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image', 'label'],
            [
                [1, 'image.png', 'hello'],
                [3, 'third.png', 'hello3'],
            ]
        );

        $this->orm = $this->withSchema(
            new Schema(
                [
                    User::class => [
                        Schema::ROLE => 'user',
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'user',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'email', 'balance'],
                        Schema::SCHEMA => [],
                        Schema::TYPECAST => ['id' => 'int', 'balance' => 'float'],
                        Schema::RELATIONS => [
                            'profile' => [
                                Relation::TYPE => Relation::HAS_ONE,
                                Relation::TARGET => Profile::class,
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [
                                    Relation::CASCADE => true,
                                    Relation::INNER_KEY => 'id',
                                    Relation::OUTER_KEY => 'user_id',
                                ],
                            ],
                        ],
                    ],
                    Profile::class => [
                        Schema::ROLE => 'profile',
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'profile',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['id', 'user_id', 'image'],
                        Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [
                            'nested' => [
                                Relation::TYPE => Relation::EMBEDDED,
                                Relation::TARGET => Nested::class,
                                Relation::LOAD => Relation::LOAD_EAGER,
                                Relation::SCHEMA => [],
                            ],
                        ],
                    ],
                    Nested::class => [
                        Schema::ROLE => 'nested',
                        Schema::MAPPER => Mapper::class,
                        Schema::DATABASE => 'default',
                        Schema::TABLE => 'profile',
                        Schema::PRIMARY_KEY => 'id',
                        Schema::COLUMNS => ['label'],
                        Schema::SCHEMA => [],
                        Schema::RELATIONS => [],
                    ],
                ]
            )
        );
    }

    public function testFetchEager(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('id', 'ASC');

        $this->assertEquals(
            [
                [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                    'profile' => [
                        'id' => 1,
                        'user_id' => 1,
                        'image' => 'image.png',
                        'nested' => [
                            'label' => 'hello',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                    'profile' => null,
                ],
                [
                    'id' => 3,
                    'email' => 'third@world.com',
                    'balance' => 150.0,
                    'profile' => [
                        'id' => 2,
                        'user_id' => 3,
                        'image' => 'third.png',
                        'nested' => [
                            'label' => 'hello3',
                        ],
                    ],
                ],
            ],
            $selector->fetchData()
        );
    }

    public function testFetchEagerReverse(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id', 'DESC');

        $this->assertEquals(
            [
                [
                    'id' => 3,
                    'email' => 'third@world.com',
                    'balance' => 150.0,
                    'profile' => [
                        'id' => 2,
                        'user_id' => 3,
                        'image' => 'third.png',
                        'nested' => [
                            'label' => 'hello3',
                        ],
                    ],
                ],
                [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                    'profile' => null,
                ],
                [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                    'profile' => [
                        'id' => 1,
                        'user_id' => 1,
                        'image' => 'image.png',
                        'nested' => [
                            'label' => 'hello',
                        ],
                    ],
                ],
            ],
            $selector->fetchData()
        );
    }
}
