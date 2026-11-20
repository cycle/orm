<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\ORM\OrmTestCase;
use Cycle\Testing\Database\Assert\QueryAssert;

class BidirectionTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testFetchData(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        \Testo\Assert::equals($selector->fetchData(), [
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
        ]);
    }

    #[\Testo\Test]
    public function testRemoveCommentFromUser(): void
    {
        /** @var User $u */
        $u = (new Select($this->orm, User::class))
            ->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::count($u->comments, 2);

        $comment = $u->comments[0];
        $u->comments->removeElement($comment);

        $this->save($u);
        \Testo\Assert::same($comment->user, $u);

        /** @var User $u2 */
        $u2 = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::count($u2->comments, 1);
        $this->orm->getHeap()->clean();
    }

    #[\Testo\Test]
    public function testRemoveFromCommentEnd(): void
    {
        $u = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::count($u->comments, 2);

        $u->comments[0]->user = null;
        QueryAssert::writes(1, fn() => $this->save($u));

        $this->orm->getHeap()->clean();
        $u = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::count($u->comments, 1);
    }

    #[\Testo\Test]
    public function testRemoveFromCommentEndDoublePersist(): void
    {
        $u = (new Select($this->orm, User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::count($u->comments, 2);

        $u->comments[0]->user = null;

        $this->save($u, $u->comments[0]);

        $u = (new Select($this->orm->withHeap(new Heap()), User::class))
            ->load('comments')
            ->wherePK(1)
            ->fetchOne();

        \Testo\Assert::count($u->comments, 1);
    }

    #[\Testo\Test]
    public function testDoubleRemoval(): void
    {
        $select = new Select($this->orm, User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::count($u->comments, 2);

        $c = $u->comments[0];
        $c->user = null;
        $u->comments->removeElement($c);
        QueryAssert::writes(1, function () use ($u, $c): void {
            $t = new Transaction($this->orm);
            $t->persist($u);
            $t->persist($c);
            $t->run();
        });

        $select = new Select($this->orm->withHeap(new Heap()), User::class);
        $u = $select->load('comments')->wherePK(1)->fetchOne();

        \Testo\Assert::count($u->comments, 1);
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
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
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'message'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
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
}
