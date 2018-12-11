<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use Spiral\ORM\Mapper\Mapper;
use Spiral\ORM\Heap\Heap;
use Spiral\ORM\Promise\PromiseInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;
use Spiral\ORM\Selector;
use Spiral\ORM\Tests\Fixtures\Nested;
use Spiral\ORM\Tests\Fixtures\Profile;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class HasOnePromiseTest extends BaseTest
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
                Schema::ALIAS        => 'profile',
                Schema::MAPPER       => Mapper::class,
                Schema::DATABASE     => 'default',
                Schema::TABLE        => 'profile',
                Schema::PRIMARY_KEY  => 'id',
                Schema::CAPTURE_KEYS => ['user_id'],
                Schema::COLUMNS      => ['id', 'user_id', 'image'],
                Schema::SCHEMA       => [],
                Schema::RELATIONS    => [
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

    public function testFetchPromises()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->profile);
        $this->assertInstanceOf(PromiseInterface::class, $b->profile);

        $this->assertInstanceOf(Profile::class, $a->profile->__resolve());
        $this->assertNull($b->profile->__resolve());

        $this->captureReadQueries();
        $this->assertSame($a->profile->__resolve(), $a->profile->__resolve());
        $this->assertNull($b->profile->__resolve());
        $this->assertNumReads(0);

        $this->assertEquals('image.png', $a->profile->__resolve()->image);
    }

    public function testFetchPromisesFromHeap()
    {
        $selector = new Selector($this->orm, User::class);
        $selector->orderBy('user.id');
        list($a, $b) = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->profile);
        $this->assertInstanceOf(PromiseInterface::class, $b->profile);

        // warm up
        (new Selector($this->orm, Profile::class))->fetchAll();

        $this->captureReadQueries();
        $this->assertSame($a->profile->__resolve(), $a->profile->__resolve());
        $this->assertNumReads(0);

        // invalid object can't be cached
        $this->captureReadQueries();
        $this->assertNull($b->profile->__resolve());
        $this->assertNumReads(1);

        $this->assertEquals('image.png', $a->profile->__resolve()->image);
    }

    public function testNoWriteOperations()
    {
        $selector = new Selector($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($u);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testRemoveAssignment()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('id')->fetchAll();

        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->run();

        // load related entity
        $this->assertNumReads(1);

        // delete related entity
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('id')->fetchAll();

        $this->assertNull($a->profile->__resolve());
        $this->assertNull($b->profile->__resolve());
    }

    public function testMoveToAnotherUser()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('id')->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $tr = new Transaction($this->orm);
        $tr->store($a);
        $tr->store($b);
        $tr->run();

        // load both promises
        $this->assertNumReads(2);

        // delete related entity
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('user.id')->load('profile')->fetchAll();

        $this->assertNull($a->profile);
        $this->assertEquals(1, $b->profile->id);
    }

    public function testMoveToAnotherUserPartial()
    {
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('id')->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $tr = new Transaction($this->orm);
        $tr->store($b);
        $tr->run();

        // load both promises
        $this->assertNumReads(2);

        // delete related entity
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Selector($this->orm, User::class);
        list($a, $b) = $selector->orderBy('user.id')->load('profile')->fetchAll();

        $this->assertNull($a->profile);
        $this->assertEquals(1, $b->profile->id);
    }
}