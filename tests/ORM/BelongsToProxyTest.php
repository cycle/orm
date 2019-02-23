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
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\ProxyFactory;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Fixtures\UserMapperWithProxy;
use Cycle\ORM\Tests\Fixtures\UserProxy;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class BelongsToProxyTest extends BaseTest
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
                [2, 'second.png']
            ]
        );

        $this->makeFK('profile', 'user_id', 'user', 'id');

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ],
            Profile::class => [
                Schema::ROLE        => 'profile',
                Schema::MAPPER      => Mapper::class,
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
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ]
                ]
            ]
        ]))->withProxyFactory(new ProxyFactory());
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        $this->assertEquals([
            [
                'id'      => 1,
                'user_id' => 1,
                'image'   => 'image.png',
            ],
            [
                'id'      => 2,
                'user_id' => 2,
                'image'   => 'second.png',
            ],
        ], $selector->fetchData());
    }

    public function testFetchProxied()
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        /**
         * @var Post $a
         * @var Post $b
         */
        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(User::class, $a->user);
        $this->assertInstanceOf(User::class, $b->user);

        $this->assertInstanceOf(UserProxy::class, $a->user);
        $this->assertInstanceOf(UserProxy::class, $b->user);

        $this->assertEquals(1, $a->user->getID());
        $this->assertEquals(2, $b->user->getID());
    }

    public function testLoaded()
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        /**
         * @var Post $a
         * @var Post $b
         */
        list($a, $b) = $selector->fetchAll();

        $this->assertFalse($a->user->__loaded());
        $this->assertEquals(1, $a->user->getID());
        $this->assertTrue($a->user->__loaded());
    }

    public function testRole()
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        /**
         * @var Post $a
         * @var Post $b
         */
        list($a, $b) = $selector->fetchAll();

        $this->assertEquals('user', $a->user->__role());

        $this->assertEquals([
            'id' => 1
        ], $a->user->__scope());
    }

    public function testScope()
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        /**
         * @var Post $a
         * @var Post $b
         */
        list($a, $b) = $selector->fetchAll();

        $this->assertEquals([
            'id' => 1
        ], $a->user->__scope());
    }
}