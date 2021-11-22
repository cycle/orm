<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;

abstract class BidirectionTest extends BaseTest
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

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer,nullable',
            'message' => 'string',
        ], [
            'user_id' => ['table' => 'user', 'column' => 'id'],
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
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
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::NULLABLE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
        ]));
    }

    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        $this->assertEquals([
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'comments' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'message' => 'msg 1',
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'message' => 'msg 2',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'comments' => [],
            ],
        ], $selector->fetchData());
    }

    public function testRemoveCommentFromUser(): void
    {
        $select = new Select($this->orm, User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(2, $u->comments);

        $u->comments->removeElement($u->comments[0]);

        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();

        $select = new Select($this->withHeap(new Heap()), User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(1, $u->comments);
    }

    public function testRemoveFromCommentEnd(): void
    {
        $select = new Select($this->orm, User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(2, $u->comments);

        $u->comments[0]->user = null;

        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->run();

        $select = new Select($this->withHeap(new Heap()), User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(1, $u->comments);
    }

    public function testRemoveFromCommentEndDoublePersist(): void
    {
        $select = new Select($this->orm, User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(2, $u->comments);

        $u->comments[0]->user = null;

        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->persist($u->comments[0]);
        $t->run();

        $select = new Select($this->withHeap(new Heap()), User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(1, $u->comments);
    }

    public function testDoubleRemoval(): void
    {
        $select = new Select($this->orm, User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(2, $u->comments);

        $c = $u->comments[0];
        $c->user = null;
        $u->comments->removeElement($c);

        $this->captureWriteQueries();
        $t = new Transaction($this->orm);
        $t->persist($u);
        $t->persist($c);
        $t->run();
        $this->assertNumWrites(1);

        $select = new Select($this->withHeap(new Heap()), User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        $this->assertCount(1, $u->comments);
    }
}
