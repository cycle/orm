<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\RefersTo;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

class RefersToProxyMapperTest extends OrmTestCase
{
    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'image' => 'image.png',
            ],
            [
                'id' => 2,
                'user_id' => 2,
                'image' => 'second.png',
            ],
            [
                'id' => 3,
                'user_id' => null,
                'image' => 'third.png',
            ],
        ]);
    }

    #[Test]
    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();
        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['user'], ReferenceInterface::class);
        Assert::instanceOf($bData['user'], ReferenceInterface::class);
        Assert::same($c->user, null);

        Assert::instanceOf($a->user, User::class);
        Assert::null($b->user);

        QueryAssert::reads(0, function () use ($a, $b): void {
            Assert::same($a->user, $a->user);
            Assert::null($b->user);
        });

        Assert::equals($a->user->email, 'hello@world.com');
    }

    #[Test]
    public function testFetchPromisesFromHeap(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();
        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        Assert::instanceOf($aData['user'], ReferenceInterface::class);
        Assert::instanceOf($bData['user'], ReferenceInterface::class);
        Assert::null($c->user);

        // warm up
        (new Select($this->orm, User::class))->fetchAll();

        QueryAssert::reads(0, function () use ($a): void {
            Assert::instanceOf($a->user, User::class);
            Assert::same($a->user, $a->user);
        });

        // invalid object can't be cached
        QueryAssert::reads(1, fn() => Assert::null($b->user));

        Assert::equals($a->user->email, 'hello@world.com');
    }

    #[Test]
    public function testNoWriteOperations(): void
    {
        $p = (new Select($this->orm, Profile::class))->wherePK(1)->fetchOne();

        QueryAssert::writes(0, fn() => $this->save($p));
    }

    #[Test]
    public function testAssignPromiseAsRelation(): void
    {
        $p = (new Select($this->orm, Profile::class))
            ->wherePK(1)->fetchOne();
        $pData = $this->extractEntity($p);

        Assert::instanceOf($pData['user'], ReferenceInterface::class);

        $np = new Profile();
        $np->image = 'new image';

        QueryAssert::reads(1, fn() => $np->user = $p->user);

        QueryAssert::count(0, 1, fn() => $this->save($np));

        $p = (new Select($this->orm->withHeap(new Heap()), Profile::class))
            ->wherePK(4)->fetchOne();

        Assert::instanceOf($p->user, User::class);
        Assert::equals($p->user->email, 'hello@world.com');
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
            'user_id' => 'integer,null',
            'image' => 'string',
        ]);

        $this->makeTable('nested', [
            'id' => 'primary',
            'profile_id' => 'integer',
            'label' => 'string',
        ]);

        $this->makeFK('nested', 'profile_id', 'profile', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
            ],
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [null, 'third.png'],
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
                Schema::RELATIONS => [],
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
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => User::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }
}
