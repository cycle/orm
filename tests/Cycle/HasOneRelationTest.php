<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests;

use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Selector\JoinableLoader;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Tests\Fixtures\Nested;
use Spiral\Cycle\Tests\Fixtures\Profile;
use Spiral\Cycle\Tests\Fixtures\User;
use Spiral\Cycle\Tests\Traits\TableTrait;
use Spiral\Cycle\Transaction;

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

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ALIAS       => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'profile' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
            ],
            Profile::class => [
                Schema::ALIAS       => 'profile',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'profile',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'image'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'nested' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'profile_id',
                        ],
                    ]
                ]
            ],
            Nested::class  => [
                Schema::ALIAS       => 'nested',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'profile_id', 'label'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Selector($this->orm, User::class);
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
        $selector = new Selector($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);

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
        $selector = new Selector($this->orm, User::class);
        $selector->load('profile');
        $result = $selector->fetchAll();

        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertInstanceOf(Profile::class, $result[0]->profile);
        $this->assertEquals('image.png', $result[0]->profile->image);

        $this->assertInstanceOf(User::class, $result[1]);
        $this->assertEquals(null, $result[1]->profile);
    }

    public function testCreateWithRelations()
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->profile = new Profile();
        $e->profile->image = "magic.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($e->profile));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->profile)->getStatus());

        $this->assertSame($e->id, $this->orm->getHeap()->get($e->profile)->getData()['user_id']);
    }

    public function testMountRelation()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Selector($this->orm, User::class);
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
        $selector = new Selector($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($orm, User::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        $this->assertSame('secondary.gif', $e->profile->image);

        $e->profile->image = 'updated.png';

        $this->captureWriteQueries();
        $tr = new Transaction($orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $tr = new Transaction($orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($orm, User::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        $this->assertSame('updated.png', $e->profile->image);
    }

    public function testDeleteChildrenByAssigningNull()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertSame(null, $e->profile);
    }

    public function testAssignNewChild()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $oP = $e->profile;
        $e->profile = new Profile();
        $e->profile->image = 'new.jpg';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertFalse($this->orm->getHeap()->has($oP));
        $this->assertTrue($this->orm->getHeap()->has($e->profile));

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertNotEquals($oP, $e->profile->id);
        $this->assertSame('new.jpg', $e->profile->image);
    }

    public function testMoveToAnotherEntity()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $this->assertNotNull($a->profile);
        $this->assertNull($b->profile);

        $p = $a->profile;
        list($b->profile, $a->profile) = [$a->profile, null];

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        $this->assertTrue($this->orm->getHeap()->has($b->profile));

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $this->assertNull($a->profile);
        $this->assertNotNull($b->profile);
        $this->assertEquals($p->id, $b->profile->id);
    }

    public function testExchange()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $b->profile = new Profile();
        $b->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($b);
        $tr->run();

        // reset state
        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();
        $this->assertSame('image.png', $a->profile->image);
        $this->assertSame('secondary.gif', $b->profile->image);

        list($a->profile, $b->profile) = [$b->profile, $a->profile];

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        // reset state
        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();
        $this->assertSame('image.png', $b->profile->image);
        $this->assertSame('secondary.gif', $a->profile->image);
    }

    public function testFetchNestedRelation()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->load('profile.nested');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'image'   => 'image.png',
                    'nested'  => [
                        'id'         => 1,
                        'profile_id' => 1,
                        'label'      => 'nested-label',
                    ]
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

    public function testFetchNestedRelationPostload()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);
        $selector->load('profile.nested');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id'      => 1,
                    'user_id' => 1,
                    'image'   => 'image.png',
                    'nested'  => [
                        'id'         => 1,
                        'profile_id' => 1,
                        'label'      => 'nested-label',
                    ]
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

    public function testUpdateNestedChild()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested->label = 'new-label';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->assertSame('new-label', $e->profile->nested->label);
    }

    public function testChangeNestedChild()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested = new Nested();
        $e->profile->nested->label = 'another';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Selector($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->assertSame('another', $e->profile->nested->label);
    }

    public function testNoWriteQueries()
    {
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested = new Nested();
        $e->profile->nested->label = 'another';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);
    }
}