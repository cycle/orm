<?php
/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Spiral\Database\Injection\Parameter;

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

        $this->makeTable('profile', [
            'id'      => 'primary',
            'user_id' => 'integer,nullable',
            'image'   => 'string'
        ]);

        $this->makeTable('nested', [
            'id'         => 'primary',
            'profile_id' => 'integer',
            'label'      => 'string'
        ]);

        $this->makeFK('profile', 'user_id', 'user', 'id');
        $this->makeFK('nested', 'profile_id', 'profile', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
            ]
        );


        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
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
                Schema::ROLE        => 'profile',
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
                Schema::ROLE        => 'nested',
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

    public function testHasInSchema()
    {
        $this->assertSame(['profile'], $this->orm->getSchema()->getRelations('user'));
    }

    public function testFetchRelation()
    {
        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Select($orm, User::class);
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

        $selector = new Select($orm, User::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        $this->assertSame('updated.png', $e->profile->image);
    }

    public function testDeleteChildrenByAssigningNull()
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertSame(null, $e->profile);
    }

    public function testDeleteNullableChild()
    {
        $this->orm = $this->withSchema(new Schema([
            User::class    => [
                Schema::ROLE        => 'user',
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
                            Relation::NULLABLE  => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ]
                ]
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
                Schema::ROLE        => 'nested',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'profile_id', 'label'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));

        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertSame(null, $e->profile);
        $this->assertSame(1, (new Select($this->orm, Profile::class))->count());
    }

    public function testAssignNewChild()
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $oP = $e->profile;
        $e->profile = new Profile();
        $e->profile->image = 'new.jpg';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->assertFalse($this->orm->getHeap()->has($oP));
        $this->assertTrue($this->orm->getHeap()->has($e->profile));

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertNotEquals($oP, $e->profile->id);
        $this->assertSame('new.jpg', $e->profile->image);
    }

    public function testMoveToAnotherEntity()
    {
        $selector = new Select($this->orm, User::class);
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

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $this->assertNull($a->profile);
        $this->assertNotNull($b->profile);
        $this->assertEquals($p->id, $b->profile->id);
    }

    public function testExchange()
    {
        $selector = new Select($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $b->profile = new Profile();
        $b->profile->image = "secondary.gif";

        $tr = new Transaction($this->orm);
        $tr->persist($b);
        $tr->run();

        // reset state
        $this->orm = $this->orm->withHeap(new Heap());

        $selector = new Select($this->orm, User::class);
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

        $selector = new Select($this->orm, User::class);
        list($a, $b) = $selector->load('profile')->orderBy('user.id')->fetchAll();
        $this->assertSame('image.png', $b->profile->image);
        $this->assertSame('secondary.gif', $a->profile->image);
    }

    public function testFetchNestedRelation()
    {
        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
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
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested->label = 'new-label';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->assertSame('new-label', $e->profile->nested->label);
    }

    public function testChangeNestedChild()
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested = new Nested();
        $e->profile->nested->label = 'another';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->assertSame('another', $e->profile->nested->label);
    }

    public function testNoWriteQueries()
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested = new Nested();
        $e->profile->nested->label = 'another';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testFindByRelatedID()
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', 1);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedIDAliased()
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile', ['as' => 'profile_relation'])->where('profile.id', 1);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedIDArray()
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', new Parameter([1]));

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedColumn()
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.image', '=', 'image.png');

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }
}