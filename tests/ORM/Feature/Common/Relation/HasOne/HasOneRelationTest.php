<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasOne;

use Cycle\Database\Injection\Parameter;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

class HasOneRelationTest extends OrmTestCase
{
    protected const NULLABLE = false;

    #[Test]
    public function testHasInSchema(): void
    {
        Assert::same($this->orm->getSchema()->getRelations('user'), ['profile']);
    }

    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id' => 1,
                    'user_id' => 1,
                    'image' => 'image.png',
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'profile' => null,
            ],
        ]);
    }

    #[Test]
    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, User::class);
        $data = $selector->with('profile')->buildQuery()->fetchAll();

        Assert::count($data[0], 3);
    }

    #[Test]
    public function testFetchRelationPostload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id' => 1,
                    'user_id' => 1,
                    'image' => 'image.png',
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'profile' => null,
            ],
        ]);
    }

    #[Test]
    public function testAccessEntities(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile');
        $result = $selector->fetchAll();

        Assert::instanceOf($result[0], User::class);
        Assert::instanceOf($result[0]->profile, Profile::class);
        Assert::equals($result[0]->profile->image, 'image.png');

        Assert::instanceOf($result[1], User::class);
        Assert::equals($result[1]->profile, null);
    }

    #[Test]
    public function testCreateWithRelations(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->profile = new Profile();
        $e->profile->image = 'magic.gif';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        Assert::true($this->orm->getHeap()->has($e->profile));
        Assert::same($this->orm->getHeap()->get($e->profile)->getStatus(), Node::MANAGED);

        Assert::same($this->orm->getHeap()->get($e->profile)->getData()['user_id'], $e->id);
    }

    #[Test]
    public function testMountRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = 'secondary.gif';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm, User::class);
        $selector->load('profile');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id' => 1,
                    'user_id' => 1,
                    'image' => 'image.png',
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'profile' => [
                    'id' => 2,
                    'user_id' => 2,
                    'image' => 'secondary.gif',
                ],
            ],
        ]);
    }

    #[Test]
    public function testCreateAndUpdateRelatedData(): void
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->where('id', 2)->fetchOne();

        $e->profile = new Profile();
        $e->profile->image = 'secondary.gif';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Select($orm, User::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        Assert::same($e->profile->image, 'secondary.gif');

        $e->profile->image = 'updated.png';

        QueryAssert::writes(1, function () use (&$tr, $orm, $e): void {
            $tr = new Transaction($orm);
            $tr->persist($e);
            $tr->run();
        });
        QueryAssert::writes(0, function () use (&$tr, $orm, $e): void {
            $tr = new Transaction($orm);
            $tr->persist($e);
            $tr->run();
        });

        // Re-select
        $orm = $this->orm->withHeap(new Heap());

        $selector = new Select($orm, User::class);
        $e = $selector->wherePK($e->id)->load('profile')->fetchOne();

        Assert::same($e->profile->image, 'updated.png');
    }

    #[Test]
    public function testDeleteChildrenByAssigningNull(): void
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        Assert::same($e->profile, null);
    }

    #[Test]
    public function testDeleteNullableChild(): void
    {
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
                            Relation::NULLABLE => true,
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
                    'nested' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
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
                Schema::RELATIONS => [],
            ],
        ]));

        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();
        $e->profile = null;

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        Assert::same($e->profile, null);
        Assert::same((new Select($this->orm, Profile::class))->count(), 1);
    }

    #[Test]
    public function testAssignNewChild(): void
    {
        $e = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->load('profile')->fetchOne();

        $oP = $e->profile;
        $e->profile = new Profile();
        $e->profile->image = 'new.jpg';

        QueryAssert::writes(2, fn() => $this->save($e));

        Assert::same($this->orm->getHeap()->has($oP), static::NULLABLE);
        Assert::true($this->orm->getHeap()->has($e->profile));

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        Assert::notEquals($e->profile->id, $oP);
        Assert::same($e->profile->image, 'new.jpg');
    }

    #[Test]
    public function testMoveToAnotherEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();

        Assert::notNull($a->profile);
        Assert::null($b->profile);

        $p = $a->profile;
        [$b->profile, $a->profile] = [$a->profile, null];

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        Assert::true($this->orm->getHeap()->has($b->profile));

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();

        Assert::null($a->profile);
        Assert::notNull($b->profile);
        Assert::equals($b->profile->id, $p->id);
    }

    #[Test]
    public function testExchange(): void
    {
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $b->profile = new Profile();
        $b->profile->image = 'secondary.gif';

        $this->save($b);

        // reset state
        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();
        Assert::same($a->profile->image, 'image.png');
        Assert::same($b->profile->image, 'secondary.gif');

        [$a->profile, $b->profile] = [$b->profile, $a->profile];

        $this->save($a, $b);

        // reset state
        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector
            ->load('profile')
            ->orderBy('user.id')->fetchAll();
        Assert::same($b->profile->image, 'image.png');
        Assert::same($a->profile->image, 'secondary.gif');
    }

    #[Test]
    public function testFetchNestedRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile.nested');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id' => 1,
                    'user_id' => 1,
                    'image' => 'image.png',
                    'nested' => [
                        'id' => 1,
                        'profile_id' => 1,
                        'label' => 'nested-label',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'profile' => null,
            ],
        ]);
    }

    #[Test]
    public function testFetchNestedRelationPostload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);
        $selector->load('profile.nested');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'profile' => [
                    'id' => 1,
                    'user_id' => 1,
                    'image' => 'image.png',
                    'nested' => [
                        'id' => 1,
                        'profile_id' => 1,
                        'label' => 'nested-label',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'profile' => null,
            ],
        ]);
    }

    #[Test]
    public function testUpdateNestedChild(): void
    {
        $selector = new Select($this->orm, User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        $e->profile->nested->label = 'new-label';

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile.nested')->fetchOne();

        Assert::same($e->profile->nested->label, 'new-label');
    }

    #[Test]
    public function testChangeNestedChild(): void
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

        Assert::same($e->profile->nested->label, 'another');
    }

    #[Test]
    public function testNoWriteQueries(): void
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

        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });
    }

    #[Test]
    public function testFindByRelatedID(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', 1);

        $result = $selector->fetchAll();
        Assert::count($result, 1);
        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
    }

    #[Test]
    public function testFindByRelatedIDAliased(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile', ['as' => 'profile_relation'])->where('profile.id', 1);

        $result = $selector->fetchAll();
        Assert::count($result, 1);
        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
    }

    #[Test]
    public function testFindByRelatedIDArray(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', new Parameter([1]));

        $result = $selector->fetchAll();
        Assert::count($result, 1);
        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
    }

    #[Test]
    public function testFindByRelatedColumn(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.image', '=', 'image.png');

        $result = $selector->fetchAll();
        Assert::count($result, 1);
        Assert::instanceOf($result[0], User::class);
        Assert::equals($result[0]->id, 1);
    }

    #[Test]
    public function testDoNotOverwriteRelation(): void
    {
        $select = new Select($this->orm, User::class);

        $u = $select->load('profile')->wherePK(1)->fetchOne();

        $newProfile = new Profile();
        $newProfile->image = 'new';
        $u->profile = $newProfile;

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        Assert::same($u2->profile->image, 'new');

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        Assert::same($u3->profile->image, 'image.png');

        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();

        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        Assert::same($u4->profile->image, 'new');
    }

    #[Test]
    public function testOverwritePromisedRelation(): void
    {
        $u = (new Select($this->orm, User::class))->wherePK(1)->fetchOne();

        $newProfile = new Profile();
        $newProfile->image = 'new';
        $u->profile = $newProfile;

        // relation is already set prior to loading
        $u2 = $this->orm->getRepository(User::class)
            ->select()
            ->load('profile')
            ->wherePK(1)->fetchOne();

        Assert::same($u2->profile->image, 'new', 'Not promised relation should not be overwritten');

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();
        Assert::same($u3->profile->image, 'image.png', 'Clrearly loaded entity has not changed fields');

        $this->save($u);

        // ovewrite values
        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        Assert::same($u4->profile->image, 'new', 'the new value should be saved');

        QueryAssert::writes(0, fn() => $this->save($u));
    }

    #[Test]
    public function testUninitializedProperty(): void
    {
        $u = new User();
        $u->email = 'many@email.com';
        $u->balance = 900;
        unset($u->profile);

        QueryAssert::writes(1, fn() => $this->save($u));

        Assert::false(isset($u->profile));

        $u->profile = null;

        QueryAssert::writes(0, fn() => $this->save($u));
    }

    /**
     * If relation property was unset - ignore this field
     */
    #[Test]
    public function testUnsetProperty(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('profile')->fetchOne();

        unset($user->profile);

        QueryAssert::writes(0, fn() => $this->save($user));
    }

    /**
     * If relation is replaced with null - delete the child (set user_id to null)
     */
    #[Test]
    public function testRemoveChildrenUsingSetNull(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('profile')->fetchOne();

        Assert::instanceOf($user->profile, Profile::class);

        QueryAssert::writes(0, fn() => $this->save($user));

        $user->profile = null;

        QueryAssert::writes(1, fn() => $this->save($user));

        QueryAssert::writes(0, fn() => $this->save($user));

        $this->orm->getHeap()->clean();
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->load('profile')->fetchOne();
        Assert::null($user->profile);
    }

    #[Test]
    public function testUpdateRelation(): void
    {
        $users = QueryAssert::reads(1, fn() => (new Select($this->orm, User::class))->fetchAll());

        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))->collect(...$users)->load('profile')->run());

        QueryAssert::reads(0, function () use ($users): void {
            Assert::notNull($users[0]->profile);
            static::NULLABLE and Assert::null($users[1]->profile);
        });
    }

    #[Test]
    public function testUpdateNestedRelation(): void
    {
        $users = QueryAssert::reads(1, fn() => (new Select($this->orm, User::class))->fetchAll());

        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))->collect(...$users)
            ->load('profile.nested')
            ->run());
        QueryAssert::reads(0, function () use ($users): void {
            foreach ($users as $user) {
                if ($user->id === 1) {
                    Assert::notNull($user->profile);
                    Assert::notNull($user->profile->nested);
                    Assert::same($user->profile->nested->label, 'nested-label');
                }
            }
        });
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('profile', [
            'id' => 'primary',
            'user_id' => 'integer,nullable',
            'image' => 'string',
        ]);

        $this->makeTable('nested', [
            'id' => 'primary',
            'profile_id' => 'integer',
            'label' => 'string',
        ]);

        $this->makeFK('profile', 'user_id', 'user', 'id');
        $this->makeFK('nested', 'profile_id', 'profile', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
            ],
        );


        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ],
        );

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
                            Relation::NULLABLE => static::NULLABLE,
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
                    'nested' => [
                        Relation::TYPE => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
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
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
