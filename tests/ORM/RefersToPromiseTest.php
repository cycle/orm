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
use Spiral\ORM\Tests\Fixtures\Profile;
use Spiral\ORM\Tests\Fixtures\User;
use Spiral\ORM\Tests\Traits\TableTrait;
use Spiral\ORM\Transaction;

abstract class RefersToPromiseTest extends BaseTest
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
            ]
        );

        $this->makeTable('profile', [
            'id'      => 'primary',
            'user_id' => 'integer,null',
            'image'   => 'string'
        ]);

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [null, 'third.png'],
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
                Schema::RELATIONS   => []
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
                    'user' => [
                        Relation::TYPE   => Relation::REFERS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id'
                        ],
                    ]
                ]
            ]
        ]));
    }

    public function testFetchRelation()
    {
        $selector = new Selector($this->orm, Profile::class);
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
            [
                'id'      => 3,
                'user_id' => null,
                'image'   => 'third.png',
            ],
        ], $selector->fetchData());
    }

    public function testFetchPromises()
    {
        $selector = new Selector($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        list($a, $b, $c) = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->user);
        $this->assertInstanceOf(PromiseInterface::class, $b->user);
        $this->assertSame(null, $c->user);

        $this->assertInstanceOf(User::class, $a->user->__resolve());
        $this->assertNull($b->user->__resolve());

        $this->captureReadQueries();
        $this->assertSame($a->user->__resolve(), $a->user->__resolve());
        $this->assertNull($b->user->__resolve());
        $this->assertNumReads(0);

        $this->assertEquals('hello@world.com', $a->user->__resolve()->email);
    }

    public function testFetchPromisesFromHeap()
    {
        $selector = new Selector($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        list($a, $b, $c) = $selector->fetchAll();

        $this->assertInstanceOf(PromiseInterface::class, $a->user);
        $this->assertInstanceOf(PromiseInterface::class, $b->user);
        $this->assertSame(null, $c->user);

        // warm up
        (new Selector($this->orm, User::class))->fetchAll();

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $a->user->__resolve());
        $this->assertSame($a->user->__resolve(), $a->user->__resolve());
        $this->assertNumReads(0);

        // invalid object can't be cached
        $this->captureReadQueries();
        $this->assertNull($b->user->__resolve());
        $this->assertNumReads(1);

        $this->assertEquals('hello@world.com', $a->user->__resolve()->email);
    }

    public function testNoWriteOperations()
    {
        $selector = new Selector($this->orm, Profile::class);
        $p = $selector->wherePK(1)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->store($p);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testAssignPromiseAsRelation()
    {
        $selector = new Selector($this->orm, Profile::class);
        $p = $selector->wherePK(1)->fetchOne();
        $this->assertInstanceOf(PromiseInterface::class, $p->user);

        $np = new Profile();
        $np->image = 'new image';

        $this->captureReadQueries();
        $np->user = $p->user;
        $this->assertNumReads(0);

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $tr = new Transaction($this->orm);
        $tr->store($np);
        $tr->run();

        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $selector = new Selector($this->orm->withHeap(new Heap()), Profile::class);
        $p = $selector->wherePK(4)->fetchOne();

        $this->assertInstanceOf(User::class, $p->user->__resolve());
        $this->assertEquals('hello@world.com', $p->user->__resolve()->email);
    }
}