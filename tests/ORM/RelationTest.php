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
use Spiral\ORM\Tests\Fixtures\Mapper\ProfileEntity;
use Spiral\ORM\Tests\Fixtures\Mapper\ProfileMapper;
use Spiral\ORM\Tests\Fixtures\Mapper\UserEntity;
use Spiral\ORM\Tests\Fixtures\Mapper\UserMapper;
use Spiral\ORM\Tests\Traits\TableTrait;

abstract class RelationTest extends BaseTest
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
            ]
        );

        $this->orm = $this->orm->withSchema(new Schema([
            UserEntity::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => UserMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'profile' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => ProfileEntity::class,
                        Relation::SCHEMA => [
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            ProfileEntity::class => [
                Schema::ALIAS       => 'profile',
                Schema::MAPPER      => ProfileMapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'image'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $selector->load('profile');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'image'   => 'image.png'
                ]
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'profile' => null
            ]
        ], $selector->fetchData());
    }

    public function testFetchRelationPostload()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $selector->load('profile', ['method' => RelationLoader::POSTLOAD]);

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'image'   => 'image.png'
                ]
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'profile' => null
            ]
        ], $selector->fetchData());
    }

    public function testAccessEntities()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $selector->load('profile');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(UserEntity::class, $result[0]);
        $this->assertInstanceOf(ProfileEntity::class, $result[0]->profile);
        $this->assertEquals('image.png', $result[0]->profile->image);

        $this->assertInstanceOf(UserEntity::class, $result[1]);
        $this->assertEquals(null, $result[1]->profile);
    }
}