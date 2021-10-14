<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\BelongsTo;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class BelongsToProxyMapperTest extends BaseTest
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
            ]
        );

        $this->getDatabase()->table('profile')->insertMultiple(
            ['user_id', 'image'],
            [
                [1, 'image.png'],
                [2, 'second.png'],
                [null, 'third.png'],
            ]
        );

        $this->getDatabase()->table('nested')->insertMultiple(
            ['profile_id', 'label'],
            [
                [1, 'nested-label'],
            ]
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
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        $this->assertInstanceOf(ReferenceInterface::class, $aData['user']);
        $this->assertInstanceOf(ReferenceInterface::class, $bData['user']);
        $this->assertInstanceOf(User::class, $a->user);
        $this->assertNull($b->user);
        $this->assertNull($c->user);


        $this->captureReadQueries();
        $this->assertSame($a->user, $a->user);
        $this->assertNull($b->user);
        $this->assertNumReads(0);

        $this->assertEquals('hello@world.com', $a->user->email);
    }

    public function testFetchPromisesFromHeap(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);

        $this->assertInstanceOf(ReferenceInterface::class, $aData['user']);
        $this->assertInstanceOf(ReferenceInterface::class, $bData['user']);
        $this->assertNull($c->user);

        // warm up
        (new Select($this->orm, User::class))->fetchAll();

        $this->captureReadQueries();
        $this->assertInstanceOf(User::class, $a->user);
        $this->assertSame($a->user, $a->user);
        $this->assertNumReads(0);

        // invalid object can't be cached
        $this->captureReadQueries();
        $this->assertNull($b->user);
        $this->assertNumReads(1);

        $this->assertEquals('hello@world.com', $a->user->email);
    }

    public function testNoWriteOperations(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $p = $selector->wherePK(1)->fetchOne();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($p);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testAssignPromiseAsRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $p = $selector->wherePK(1)->fetchOne();

        $pData = $this->extractEntity($p);
        $this->assertInstanceOf(ReferenceInterface::class, $pData['user']);

        $np = new Profile();
        $np->image = 'new image';

        $this->captureReadQueries();
        $np->user = $pData['user'];
        $this->assertNumReads(0);

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($np);

        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $p = (new Select($this->orm->withHeap(new Heap()), Profile::class))->wherePK(4)->fetchOne();

        $this->assertInstanceOf(User::class, $p->user);
        $this->assertEquals('hello@world.com', $p->user->email);
    }

    public function testEditPromised(): void
    {
        $p = (new Select($this->orm, Profile::class))
            ->wherePK(1)->fetchOne();
        $p->user->balance = 400;

        $this->captureWriteQueries();
        $this->captureReadQueries();

        $this->save($p);

        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        $p = (new Select($this->orm->withHeap(new Heap()), Profile::class))
            ->wherePK(1)->fetchOne();

        $this->assertSame(400, (int)$p->user->balance);
    }
}
