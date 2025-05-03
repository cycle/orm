<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Cycle\Database\Injection\Parameter;

abstract class HasOneRelationTest extends BaseTest
{
    use TableTrait;

    protected const NULLABLE = false;

    public function testHasInSchema(): void
    {
        $this->assertSame(['profile'], $this->orm->getSchema()->getRelations('user'));
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testWithNoColumns(): void
    {
        $selector = new Select($this->orm, User::class);
        $data = $selector->with('profile')->buildQuery()->fetchAll();

        $this->assertCount(3, $data[0]);
    }

    public function testFetchRelationPostload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testAccessEntities(): void
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

        $this->assertEquals(3, $e->id);

        $this->assertTrue($this->orm->getHeap()->has($e));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e)->getStatus());

        $this->assertTrue($this->orm->getHeap()->has($e->profile));
        $this->assertSame(Node::MANAGED, $this->orm->getHeap()->get($e->profile)->getStatus());

        $this->assertSame($e->id, $this->orm->getHeap()->get($e->profile)->getData()['user_id']);
    }

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

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

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

        $this->assertSame(null, $e->profile);
    }

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

        $this->assertSame(null, $e->profile);
        $this->assertSame(1, (new Select($this->orm, Profile::class))->count());
    }

    public function testAssignNewChild(): void
    {
        $e = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->load('profile')->fetchOne();

        $oP = $e->profile;
        $e->profile = new Profile();
        $e->profile->image = 'new.jpg';

        $this->captureWriteQueries();
        $this->save($e);
        $this->assertNumWrites(2);

        $this->assertSame(static::NULLABLE, $this->orm->getHeap()->has($oP));
        $this->assertTrue($this->orm->getHeap()->has($e->profile));

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $e = $selector->wherePK(1)->load('profile')->fetchOne();

        $this->assertNotEquals($oP, $e->profile->id);
        $this->assertSame('new.jpg', $e->profile->image);
    }

    public function testMoveToAnotherEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $this->assertNotNull($a->profile);
        $this->assertNull($b->profile);

        $p = $a->profile;
        [$b->profile, $a->profile] = [$a->profile, null];

        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();

        $this->assertTrue($this->orm->getHeap()->has($b->profile));

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        [$a, $b] = $selector->load('profile')->orderBy('user.id')->fetchAll();

        $this->assertNull($a->profile);
        $this->assertNotNull($b->profile);
        $this->assertEquals($p->id, $b->profile->id);
    }

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
        $this->assertSame('image.png', $a->profile->image);
        $this->assertSame('secondary.gif', $b->profile->image);

        [$a->profile, $b->profile] = [$b->profile, $a->profile];

        $this->save($a, $b);

        // reset state
        $this->orm->getHeap()->clean();

        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector
            ->load('profile')
            ->orderBy('user.id')->fetchAll();
        $this->assertSame('image.png', $b->profile->image);
        $this->assertSame('secondary.gif', $a->profile->image);
    }

    public function testFetchNestedRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile.nested');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testFetchNestedRelationPostload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile', ['method' => JoinableLoader::POSTLOAD]);
        $selector->load('profile.nested');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

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

        $this->assertSame('new-label', $e->profile->nested->label);
    }

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

        $this->assertSame('another', $e->profile->nested->label);
    }

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

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testFindByRelatedID(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', 1);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedIDAliased(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile', ['as' => 'profile_relation'])->where('profile.id', 1);

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedIDArray(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.id', new Parameter([1]));

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testFindByRelatedColumn(): void
    {
        $selector = new Select($this->orm, User::class);

        $selector->with('profile')->where('profile.image', '=', 'image.png');

        $result = $selector->fetchAll();
        $this->assertCount(1, $result);
        $this->assertInstanceOf(User::class, $result[0]);
        $this->assertEquals(1, $result[0]->id);
    }

    public function testDoNotOverwriteRelation(): void
    {
        $select = new Select($this->orm, User::class);

        $u = $select->load('profile')->wherePK(1)->fetchOne();

        $newProfile = new Profile();
        $newProfile->image = 'new';
        $u->profile = $newProfile;

        $u2 = $this->orm->getRepository(User::class)->findByPK(1);
        $this->assertSame('new', $u2->profile->image);

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        $this->assertSame('image.png', $u3->profile->image);

        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();

        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        $this->assertSame('new', $u4->profile->image);
    }

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

        $this->assertSame('new', $u2->profile->image, 'Not promised relation should not be overwritten');

        $u3 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();
        $this->assertSame('image.png', $u3->profile->image, 'Clrearly loaded entity has not changed fields');

        $this->save($u);

        // ovewrite values
        $u4 = $this->orm->withHeap(new Heap())->getRepository(User::class)
            ->select()->load('profile')->wherePK(1)->fetchOne();

        $this->assertSame('new', $u4->profile->image, 'the new value should be saved');

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    public function testUninitializedProperty(): void
    {
        $u = new User();
        $u->email = 'many@email.com';
        $u->balance = 900;
        unset($u->profile);

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(1);

        self::assertFalse(isset($u->profile));

        $u->profile = null;

        $this->captureWriteQueries();
        $this->save($u);
        $this->assertNumWrites(0);
    }

    /**
     * If relation property was unset - ignore this field
     */
    public function testUnsetProperty(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('profile')->fetchOne();

        unset($user->profile);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);
    }

    /**
     * If relation is replaced with null - delete the child (set user_id to null)
     */
    public function testRemoveChildrenUsingSetNull(): void
    {
        /** @var User $user */
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('profile')->fetchOne();

        $this->assertInstanceOf(Profile::class, $user->profile);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);

        $user->profile = null;

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(1);

        $this->captureWriteQueries();
        $this->save($user);
        $this->assertNumWrites(0);

        $this->orm->getHeap()->clean();
        $user = (new Select($this->orm, User::class))
            ->wherePK(1)
            ->with('profile')->fetchOne();
        static::NULLABLE
            ? $this->assertNull($user->profile)
            // Because not nullable HAS_ONE loads the child using INNER JOIN
            : $this->assertNull($user);
    }

    public function setUp(): void
    {
        parent::setUp();

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
