<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Profile;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class BelongsToNativeReferenceTest extends BaseTest
{
    use TableTrait;

    public function setUp(): void
    {
        parent::setUp();

        $this->makeTable('user', [
            'id'      => 'primary',
            'email'   => 'string',
            'balance' => 'float'
        ]);

        $this->makeTable('profile', [
            'id'      => 'primary',
            'user_id' => 'integer,null',
            'image'   => 'string'
        ]);

        $this->makeTable('nested', [
            'id'         => 'primary',
            'profile_id' => 'integer',
            'label'      => 'string'
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
            User::class    => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
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
                    'user' => [
                        Relation::TYPE   => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::LOAD   => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE  => true
                        ],
                    ]
                ]
            ]
        ]))->withPromiseFactory(null);
    }

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, Profile::class);
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

    public function testFetchPromises(): void
    {
        $selector = new Select($this->orm, Profile::class);
        $selector->orderBy('profile.id');
        [$a, $b, $c] = $selector->fetchAll();

        $aData = $this->extractEntity($a);
        $bData = $this->extractEntity($b);
        $cData = $this->extractEntity($c);

        $this->assertInstanceOf(ReferenceInterface::class, $aData['user']);
        $this->assertInstanceOf(ReferenceInterface::class, $bData['user']);
        $this->assertInstanceOf(ReferenceInterface::class, $cData['user']);
        $this->assertSame(null, $c->user);
    }

    public function testNoWriteOperations(): void
    {
        /** @var Profile $p */
        $p = (new Select($this->orm, Profile::class))
            ->wherePK(1)->fetchOne();

        $pData = $this->extractEntity($p);

        $this->assertInstanceOf(ReferenceInterface::class, $pData['user']);

        $this->captureWriteQueries();
        $this->save($p);
        $this->assertNumWrites(0);
    }

    public function testCreateWithoutObject(): void
    {
        $p = $this->orm->make(Profile::class);
        $p->user = new Reference('user', ['id' => 1]);
        $p->image = 'test.png';

        $this->captureReadQueries();
        $this->captureWriteQueries();

        $this->save($p);

        $this->assertNumWrites(1);
        $this->assertNumReads(0);

        // new orm isolated part of memory
        $orm = $this->orm->withHeap(new Heap());

        $pp = (new Select($orm, Profile::class))->wherePK($p->id)->load('user')->fetchOne();

        $this->assertEquals('test.png', $pp->image);
        $this->assertEquals(1, $pp->user->id);
        $this->assertEquals('hello@world.com', $pp->user->email);
    }
}
