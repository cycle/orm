<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Heap;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\State;
use Spiral\ORM\Tests\Fixtures\Mapper\ProfileEntity;
use Spiral\ORM\Tests\Fixtures\Mapper\ProfileMapper;
use Spiral\ORM\Tests\Fixtures\Mapper\UserEntity;
use Spiral\ORM\Tests\Fixtures\Mapper\UserMapper;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class HasOneRelationTest extends BaseTest
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

    public function testCreateWithRelations()
    {
        $e = new UserEntity();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->profile = new ProfileEntity();
        $e->profile->image = "magic.gif";

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(State::LOADED, $this->orm->getHeap()->get($e)->getState());

        $this->assertTrue($this->orm->getHeap()->has($e->profile));
        $this->assertSame(State::LOADED, $this->orm->getHeap()->get($e->profile)->getState());

        $this->assertSame($e->id, $this->orm->getHeap()->get($e->profile)->getData()['user_id']);
    }

    public function testMountRelation()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new ProfileEntity();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

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
                'profile' => [
                    'id'      => 2,
                    'user_id' => 2,
                    'image'   => 'secondary.gif'
                ]
            ]
        ], $selector->fetchData());
    }

    public function testCreateAndUpdateRelatedData()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new ProfileEntity();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($orm, UserEntity::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        $this->assertSame('secondary.gif', $e->profile->image);

        $e->profile->image = 'updated.png';
        $tr = new Transaction($orm);
        $tr->store($e);
        $tr->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($orm, UserEntity::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        $this->assertSame('updated.png', $e->profile->image);
    }

    public function testDeleteChildrenByAssigningNull()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), UserEntity::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertSame(null, $e->profile);
    }

    public function testAssignNewChild()
    {
        $selector = new Selector($this->orm, UserEntity::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $oP = $e->profile;
        $e->profile = new ProfileEntity();
        $e->profile->image = 'new.jpg';

        $tr = new Transaction($this->orm);
        $tr->store($e);
        $tr->run();

        $this->assertFalse($this->orm->getHeap()->has($oP));
        $this->assertTrue($this->orm->getHeap()->has($e->profile));

        $selector = new Selector($this->orm->withHeap(new Heap()), UserEntity::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertNotEquals($oP, $e->profile->id);
        $this->assertSame('new.jpg', $e->profile->image);
    }

//    public function testMoveToAnotherEntity()
//    {
//        $selector = new Selector($this->orm, UserEntity::class);
//        list($a, $b) = $selector->load('profile')->fetchAll();
//
//        $this->assertNotNull($a->profile);
//        $this->assertNull($b->profile);
//
//        $p = $a->profile;
//        list($b->profile, $a->profile) = [$a->profile, null];
//
//        $this->enableProfiling();
//        $tr = new Transaction($this->orm);
//        $tr->store($a);
//        $tr->store($b);
//        $tr->run();
//
//        $this->disableProfiling();
//
//        $this->assertFalse($this->orm->getHeap()->has($b->profile));
//
//        $selector = new Selector($this->orm->withHeap(new Heap()), UserEntity::class);
//        list($a, $b) = $selector->load('profile')->fetchAll();
//
//        $this->assertNull($a->profile);
//        $this->assertNotNull($b->profile);
//        $this->assertEquals($p->id, $b->profile->id);
//    }
}