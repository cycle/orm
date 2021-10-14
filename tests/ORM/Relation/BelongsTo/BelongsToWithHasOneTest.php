<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\BelongsTo;

use Cycle\ORM\Exception\Relation\NullException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Cycle\Database\Injection\Parameter;

abstract class BelongsToWithHasOneTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer',
            'image' => 'string',
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
            'id' => 'primary',
            'profile_id' => 'integer',
            'label' => 'string',
        ]);

        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ]
        );

        $this->makeFK('profile', 'user_id', 'user', 'id');
        $this->makeFK('nested', 'profile_id', 'profile', 'id');

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Profile::class,
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
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                    'nested' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
                            Relation::NULLABLE => true, // todo set false and connect with nested:profile
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'profile_id',
                        ],
                    ],
                ],
            ],
            Nested::class => [
                Schema::ROLE => 'nested',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'profile_id', 'label'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'profile' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => Profile::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'profile_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user')->orderBy('profile.id');

        $this->assertEquals([
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
                'user' => [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], $selector->fetchData());
    }

    public function testFetchLimitAndSortByParent(): void
    {
        $selector = new Select($this->orm, Profile::class);

        $selector
            ->with('user', ['as' => 'user'])
            ->load('user', ['using' => 'user'])
            ->orderBy('user.id', 'DESC')
            ->orderBy('id', 'DESC')
            ->limit(1);

        $this->assertEquals([
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], $selector->fetchData());
    }

    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $data = $selector->with('user')->buildQuery()->fetchAll();

        $this->assertCount(3, $data[0]);
    }

    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user', ['method' => Select\JoinableLoader::INLOAD])
                 ->orderBy('profile.id');

        $this->assertEquals([
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
                'user' => [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'image' => 'third.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], $selector->fetchData());
    }

    public function testAccessEntities(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->load('user')->orderBy('profile.id');
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

    public function testCreateWithOnlyBelongsToRelation(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 300;

        $p = new Profile();
        $p->image = 'magic.gif';
        $p->user = $u;

        $this->save($p);

        $this->assertEquals(3, $u->id);
        $this->assertEquals(4, $p->id);

        $this->assertTrue($this->orm->getHeap()->has($u));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($u)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($p));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($p)->getStatus());

        $this->assertSame($u->id, $this->orm->getHeap()->get($p)->getData()['user_id']);

        $selector = new Select($this->orm, Profile::class);
        $selector->load('user');

        $this->assertEquals([
            [
                'id' => 4,
                'user_id' => 3,
                'image' => 'magic.gif',
                'user' => [
                    'id' => 3,
                    'email' => 'test@email.com',
                    'balance' => 300.0,
                ],
            ],
        ], $selector->wherePK(4)->fetchData());
    }

    public function testCreateWithOnlyHasOneRelation(): void
    {
        $p = new Profile();
        $p->image = 'magic.gif';

        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 300;
        $u->profile = $p;

        $this->save($u);

        $this->assertEquals(3, $u->id);
        $this->assertEquals(4, $p->id);
        // todo
        // $this->assertNotNull($p->user);

        $this->assertTrue($this->orm->getHeap()->has($u));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($u)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($p));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($p)->getStatus());

        $this->assertSame($u->id, $this->orm->getHeap()->get($p)->getData()['user_id']);

        $selector = new Select($this->orm, Profile::class);
        $selector->load('user');

        $this->assertEquals([
            [
                'id' => 4,
                'user_id' => 3,
                'image' => 'magic.gif',
                'user' => [
                    'id' => 3,
                    'email' => 'test@email.com',
                    'balance' => 300.0,
                ],
            ],
        ], $selector->wherePK(4)->fetchData());
    }

    public function testDeleteFromOnlyBelongsToRelation(): void
    {
        $p = new Profile();
        $p->image = 'magic.gif';

        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 300;
        $u->profile = $p;
        $p->user = $u;

        $this->save($u);

        $p->user = null;

        $this->expectException(NullException::class);
        try {
            $this->save($p);
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // we do not expect state to be consistent as transaction failed, see rollback tests
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

    public function testDeleteFromOnlyHasOneRelation(): void
    {
        $p = new Profile();
        $p->image = 'magic.gif';

        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 300;
        $u->profile = $p;
        $p->user = $u;

        $this->save($u);

        $u->profile = null;
        $this->save($u);

        $this->assertNull($u->profile);
        $this->assertFalse($this->orm->getHeap()->has($p));
    }

    public function testNoWriteQueries(): void
    {
        $u = new User();
        $u->email = 'test@email.com';
        $u->balance = 300;

        $p = new Profile();
        $p->image = 'magic.gif';
        $p->user = $u;

        $this->save($p);

        $this->orm = $this->orm->withHeap(new Heap());
        $p = (new Select($this->orm, Profile::class))->load('user')->wherePK(4)->fetchOne();

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);
    }

    public function testSetExistedParent(): void
    {
        $s = new Select($this->orm, User::class);
        $u = $s->wherePK(1)->fetchOne();

        $p = new Profile();
        $p->image = 'magic.gif';
        $p->user = $u;

        $tr = new Transaction($this->orm);
        $tr->persist($p);
        $tr->run();

        $this->assertEquals(1, $u->id);
        $this->assertEquals(4, $p->id);

        $this->assertTrue($this->orm->getHeap()->has($p));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($p)->getStatus());

        $this->assertSame($u->id, $this->orm->getHeap()->get($p)->getData()['user_id']);

        $selector = new Select($this->orm, Profile::class);
        $selector->load('user');

        $this->assertEquals([
            [
                'id' => 4,
                'user_id' => 1,
                'image' => 'magic.gif',
                'user' => [
                    'id' => 1,
                    'email' => 'hello@world.com',
                    'balance' => 100.0,
                ],
            ],
        ], $selector->wherePK(4)->fetchData());
    }

    public function testChangeParent(): void
    {
        $s = new Select($this->orm, Profile::class);
        $p = $s->wherePK(1)->load('user')->fetchOne();

        $s = new Select($this->orm, User::class);
        $u = $s->wherePK(2)->fetchOne();

        $p->user = $u;

        $tr = new Transaction($this->orm);
        $tr->persist($p);
        $tr->run();

        $this->assertEquals([
            [
                'id' => 1,
                'user_id' => 2,
                'image' => 'image.png',
                'user' => [
                    'id' => 2,
                    'email' => 'another@world.com',
                    'balance' => 200.0,
                ],
            ],
        ], (new Select($this->orm, Profile::class))->load('user')->wherePK(
            1
        )->fetchData());
    }

    public function testExchangeParents(): void
    {
        /**
         * @var Profile $a
         * @var Profile $b
         */
        [$a, $b] = (new Select($this->orm, Profile::class))
            ->wherePK(new Parameter([1, 2]))->orderBy('profile.id')
            ->load('user')->fetchAll();

        [$a->user, $b->user] = [$b->user, $a->user];
        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(2);

        $this->captureWriteQueries();
        $this->save($a, $b);
        $this->assertNumWrites(0);

        $s = new Select($this->orm->withHeap(new Heap()), Profile::class);
        [$a2, $b2] = $s->wherePK(new Parameter([1, 2]))->orderBy('profile.id')
                       ->load('user')->fetchAll();

        $this->assertSame($a->user->id, $a2->user->id);
        $this->assertSame($b->user->id, $b2->user->id);
    }

    public function testSetNullException(): void
    {
        $this->expectException(NullException::class);

        $s = new Select($this->orm, Profile::class);
        $p = $s->wherePK(1)->load('user')->fetchOne();
        $p->user = null;

        try {
            $tr = new Transaction($this->orm);
            $tr->persist($p);
            $tr->run();
        } catch (\Throwable $e) {
            throw $e;
        } finally {
            // we do not expect state to be consistent as transaction failed, see rollback tests
            $this->orm = $this->orm->withHeap(new Heap());
        }
    }

    public function testNested(): void
    {
        $n = new Nested();
        $n->label = 'nested label';
        $n->profile = new Profile();
        $n->profile->image = 'profile';
        $n->profile->user = new User();
        $n->profile->user->email = 'new@email.com';
        $n->profile->user->balance = 999;

        $this->captureWriteQueries();
        $this->save($n);
        $this->assertNumWrites(3);

        $this->captureWriteQueries();
        $this->save($n);
        $this->assertNumWrites(0);

        $n = (new Select($this->orm->withHeap(new Heap()), Nested::class))
            ->wherePK(2)->load('profile.user')->fetchOne();

        $this->assertSame('profile', $n->profile->image);
        $this->assertSame('new@email.com', $n->profile->user->email);
    }

    public function testWhereNested(): void
    {
        $s = new Select($this->orm->withHeap(new Heap()), Nested::class);
        $n = $s->with('profile.user')
               ->where('profile.user.id', 1)
               ->fetchOne();

        $this->assertSame('nested-label', $n->label);
    }

    public function testWhereNestedWithAlias(): void
    {
        $s = new Select($this->orm->withHeap(new Heap()), Nested::class);
        $n = $s
            ->with('profile.user', ['as' => 'u'])
            ->where('u.id', 1)
            ->fetchOne();

        $this->assertSame('nested-label', $n->label);
    }
}
