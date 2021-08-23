<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Nested;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Cycle\Database\Injection\Parameter;

abstract class LinkedTreeTest extends BaseTest
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

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->makeTable('nested', [
            'id'       => 'primary',
            'user_id'  => 'integer',
            'owner_id' => 'integer',
            'label'    => 'string'
        ]);

        $this->makeFK('nested', 'user_id', 'user', 'id');

        $this->getDatabase()->table('nested')->insertMultiple(
            ['user_id', 'owner_id', 'label'],
            [
                [1, 2, 'nested-label'],
            ]
        );

        $this->orm = $this->withSchema(new Schema([
            User::class   => [
                Schema::ROLE        => 'user',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'email', 'balance'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => [
                    'nested' => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                    'owned'  => [
                        Relation::TYPE   => Relation::HAS_ONE,
                        Relation::TARGET => Nested::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE   => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'owner_id',
                        ],
                    ],
                ]
            ],
            Nested::class => [
                Schema::ROLE        => 'nested',
                Schema::MAPPER      => Mapper::class,
                Schema::DATABASE    => 'default',
                Schema::TABLE       => 'nested',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS     => ['id', 'user_id', 'owner_id', 'label'],
                Schema::SCHEMA      => [],
                Schema::RELATIONS   => []
            ]
        ]));
    }

    public function testFetchRelations(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load(['nested', 'owned'])->orderBy('user.id');

        $this->assertEquals([
            [
                'id'      => 1,
                'email'   => 'hello@world.com',
                'balance' => 100.0,
                'nested'  => [
                    'id'       => 1,
                    'user_id'  => 1,
                    'owner_id' => 2,
                    'label'    => 'nested-label',
                ],
                'owned'   => null,
            ],
            [
                'id'      => 2,
                'email'   => 'another@world.com',
                'balance' => 200.0,
                'nested'  => null,
                'owned'   => [
                    'id'       => 1,
                    'user_id'  => 1,
                    'owner_id' => 2,
                    'label'    => 'nested-label',
                ],
            ],
        ], $selector->fetchData());
    }

    public function testCreateDoubleLinked(): void
    {
        $u1 = new User();
        $u1->email = 'u1@email.com';
        $u1->balance = 900;

        $u2 = new User();
        $u2->email = 'u2@email.com';
        $u2->balance = 100;

        $n = new Nested();
        $n->label = 'nested to u1 and u2';

        $u1->nested = $n;
        $u2->owned = $n;

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($u1);
        $tr->persist($u2);
        $tr->run();

        $this->assertNumWrites(3);

        $selector = new Select($this->orm, User::class);
        $selector->load(['nested', 'owned'])->where('user.id', new Parameter([3, 4]))->orderBy('user.id');

        $this->assertEquals([
            [
                'id'      => 3,
                'email'   => 'u1@email.com',
                'balance' => 900.0,
                'nested'  => [
                    'id'       => 2,
                    'user_id'  => 3,
                    'owner_id' => 4,
                    'label'    => 'nested to u1 and u2',
                ],
                'owned'   => null,
            ],
            [
                'id'      => 4,
                'email'   => 'u2@email.com',
                'balance' => 100.0,
                'nested'  => null,
                'owned'   => [
                    'id'       => 2,
                    'user_id'  => 3,
                    'owner_id' => 4,
                    'label'    => 'nested to u1 and u2',
                ],
            ],
        ], $selector->fetchData());

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load(['nested', 'owned'])->where('user.id', new Parameter([3, 4]))->orderBy('user.id');

        [$u1, $u2] = $selector->fetchAll();
        $this->assertSame($u1->nested, $u2->owned);
    }
}
