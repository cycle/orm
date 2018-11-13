<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\EntityMapper;
use Spiral\ORM\Tests\Fixtures\Nested;
use Spiral\ORM\Tests\Fixtures\Profile;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;

abstract class BelongsToRelationTest extends BaseTest
{
    use TableTrait;

    public function setUp()
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('profile', [
            'id'      => 'primary',
            'user_id' => 'integer',
            'image'   => 'string'
        ]);

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [2, 'third.png'],
            ]
        );

        $this->makeTable('nested', [
            'id'         => 'primary',
            'profile_id' => 'integer',
            'label'      => 'string'
        ]);

        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            Profile::class => [
                Schema::ALIAS       => 'profile',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'image'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'user' => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ]
                ]
            ],
            Nested::class  => [
                Schema::ALIAS       => 'nested',
                Schema::MAPPER      => EntityMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'profile_id', 'label'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'profile' => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::INNER_KEY => 'profile_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ]
                ]
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Selector($this->orm, Profile::class);
        $selector->load('user');

        $this->assertEquals([
            [
                'id'      => 1,
                'user_id' => 1,
                'image'   => 'image.png',
                'user'    => [
                    'id'      => 1,
                    'email'   => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id'      => 2,
                'user_id' => 2,
                'image'   => 'second.png',
                'user'    => [
                    'id'      => 2,
                    'email'   => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id'      => 3,
                'user_id' => 2,
                'image'   => 'third.png',
                'user'    => [
                    'id'      => 2,
                    'email'   => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], $selector->fetchData());
    }

    public function testFetchRelationInload()
    {
        $selector = new Selector($this->orm, Profile::class);
        $selector->load('user', ['method' => RelationLoader::INLOAD]);

        $this->assertEquals([
            [
                'id'      => 1,
                'user_id' => 1,
                'image'   => 'image.png',
                'user'    => [
                    'id'      => 1,
                    'email'   => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id'      => 2,
                'user_id' => 2,
                'image'   => 'second.png',
                'user'    => [
                    'id'      => 2,
                    'email'   => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id'      => 3,
                'user_id' => 2,
                'image'   => 'third.png',
                'user'    => [
                    'id'      => 2,
                    'email'   => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], $selector->fetchData());
    }

    public function testAccessEntities()
    {
        $selector = new Selector($this->orm, Profile::class);
        $selector->load('user');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(Profile::class, $result[0]);
        $this->assertInstanceOf(User::class, $result[0]->user);
        $this->assertEquals('hello@world.com', $result[0]->user->email);

        $this->assertInstanceOf(Profile::class, $result[1]);
        $this->assertInstanceOf(User::class, $result[1]->user);
        $this->assertEquals('another@world.com', $result[1]->user->email);

        $this->assertInstanceOf(Profile::class, $result[2]);
        $this->assertInstanceOf(User::class, $result[2]->user);
        $this->assertEquals('another@world.com', $result[2]->user->email);

        $this->assertSame($result[1]->user, $result[2]->user);
    }
}