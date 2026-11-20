<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasOne;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasOneProxyMapperTest extends OrmTestCase
{
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
    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        Assert::instanceOf($a->profile, Profile::class);
        Assert::null($b->profile);

        QueryAssert::reads(0, function () use ($a, $b): void {
            Assert::same($a->profile, $a->profile);
            Assert::null($b->profile);
        });

        Assert::equals($a->profile->image, 'image.png');
    }

    #[Test]
    public function testOnePromiseLoaded(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        Assert::false($aData['profile']->hasValue());
        Assert::instanceOf($a->profile, Profile::class);
        Assert::true($aData['profile']->hasValue());
    }

    #[Test]
    public function testOnePromiseRole(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        Assert::same($aData['profile']->getRole(), 'profile');
    }

    #[Test]
    public function testOnePromiseScope(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        Assert::equals($aData['profile']->getScope(), ['user_id' => 1]);
    }

    #[Test]
    public function testFetchPromisesFromHeap(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        // warm up
        (new Select($this->orm, Profile::class))->fetchAll();

        QueryAssert::reads(0, fn() => Assert::same($a->profile, $a->profile));

        // invalid object can't be cached
        QueryAssert::reads(1, fn() => Assert::null($b->profile));

        Assert::equals($a->profile->image, 'image.png');
    }

    #[Test]
    public function testNoWriteOperations(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        QueryAssert::writes(0, function () use ($u): void {
            $tr = new Transaction($this->orm);
            $tr->persist($u);
            $tr->run();
        });
    }

    #[Test]
    public function testRemoveAssignment(): void
    {
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();

        $a->profile = null;

        QueryAssert::count(1, 1, fn() => $this->save($a));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();

        Assert::null($a->profile);
        Assert::null($b->profile);
    }

    #[Test]
    public function testMoveToAnotherUser(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))
            ->orderBy('id')
            ->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        QueryAssert::count(1, 1, fn() => $this->save($a, $b));

        $this->orm->getHeap()->clean();
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('user.id')->load('profile')->fetchAll();

        Assert::null($a->profile);
        Assert::equals($b->profile->id, 1);
    }

    #[Test]
    public function testMoveToAnotherUserPartial(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))
            ->orderBy('id')
            ->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        QueryAssert::count(1, 1, fn() => $this->save($b));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('user.id')->load('profile')->fetchAll();

        Assert::null($a->profile);
        Assert::equals($b->profile->id, 1);
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
            'user_id' => 'integer',
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
                        Relation::LOAD => Relation::LOAD_PROMISE,
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
                Schema::FIND_BY_KEYS => ['user_id'],
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
