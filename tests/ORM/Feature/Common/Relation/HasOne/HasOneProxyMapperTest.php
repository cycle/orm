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
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class HasOneProxyMapperTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('profile');

        \Testo\Assert::equals($selector->fetchData(), [
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

    #[\Testo\Test]
    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        \Testo\Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        \Testo\Assert::instanceOf($a->profile, Profile::class);
        \Testo\Assert::null($b->profile);

        $this->captureReadQueries();
        \Testo\Assert::same($a->profile, $a->profile);
        \Testo\Assert::null($b->profile);
        $this->assertNumReads(0);

        \Testo\Assert::equals($a->profile->image, 'image.png');
    }

    #[\Testo\Test]
    public function testOnePromiseLoaded(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        \Testo\Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        \Testo\Assert::false($aData['profile']->hasValue());
        \Testo\Assert::instanceOf($a->profile, Profile::class);
        \Testo\Assert::true($aData['profile']->hasValue());
    }

    #[\Testo\Test]
    public function testOnePromiseRole(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        \Testo\Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        \Testo\Assert::same($aData['profile']->getRole(), 'profile');
    }

    #[\Testo\Test]
    public function testOnePromiseScope(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        \Testo\Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        \Testo\Assert::equals($aData['profile']->getScope(), ['user_id' => 1]);
    }

    #[\Testo\Test]
    public function testFetchPromisesFromHeap(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        \Testo\Assert::instanceOf($aData['profile'], ReferenceInterface::class);
        \Testo\Assert::instanceOf($bData['profile'], ReferenceInterface::class);

        // warm up
        (new Select($this->orm, Profile::class))->fetchAll();

        $this->captureReadQueries();
        \Testo\Assert::same($a->profile, $a->profile);
        $this->assertNumReads(0);

        // invalid object can't be cached
        $this->captureReadQueries();
        \Testo\Assert::null($b->profile);
        $this->assertNumReads(1);

        \Testo\Assert::equals($a->profile->image, 'image.png');
    }

    #[\Testo\Test]
    public function testNoWriteOperations(): void
    {
        $selector = new Select($this->orm, User::class);
        $u = $selector->wherePK(1)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u);
        $tr->run();
        $this->assertNumWrites(0);
    }

    #[\Testo\Test]
    public function testRemoveAssignment(): void
    {
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();

        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($a);

        // load related entity
        $this->assertNumReads(1);

        // Delete related entity
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('id')->fetchAll();

        \Testo\Assert::null($a->profile);
        \Testo\Assert::null($b->profile);
    }

    #[\Testo\Test]
    public function testMoveToAnotherUser(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))
            ->orderBy('id')
            ->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($a, $b);

        // Load profile 2
        $this->assertNumReads(1);

        // delete related entity
        $this->assertNumWrites(1);

        $this->orm->getHeap()->clean();
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('user.id')->load('profile')->fetchAll();

        \Testo\Assert::null($a->profile);
        \Testo\Assert::equals($b->profile->id, 1);
    }

    #[\Testo\Test]
    public function testMoveToAnotherUserPartial(): void
    {
        [$a, $b] = (new Select($this->orm, User::class))
            ->orderBy('id')
            ->fetchAll();

        $b->profile = $a->profile;
        $a->profile = null;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($b);

        // Load profile 2
        $this->assertNumReads(1);

        // delete related entity
        $this->assertNumWrites(1);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        [$a, $b] = $selector->orderBy('user.id')->load('profile')->fetchAll();

        \Testo\Assert::null($a->profile);
        \Testo\Assert::equals($b->profile->id, 1);
    }

    #[\Testo\Lifecycle\BeforeTest]
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
