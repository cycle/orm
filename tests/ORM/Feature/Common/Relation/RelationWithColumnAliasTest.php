<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation;

use Cycle\Database\Injection\Expression;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Doctrine\Common\Collections\Collection;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class RelationWithColumnAliasTest extends OrmTestCase
{
    #[Test]
    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
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

    #[Test]
    public function testFetchRelationInload(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => JoinableLoader::INLOAD])->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
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

    #[Test]
    public function testFetchRelationInloadNativeColumnName(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => JoinableLoader::INLOAD])->orderBy('user.id_int');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
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

    #[Test]
    public function testFetchOneWhere(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => JoinableLoader::INLOAD])
            ->where('id', 1)
            ->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testFetchOneWhereTargeted(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments', ['method' => JoinableLoader::INLOAD])
            ->where('@.id', 1)
            ->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testFetchOneWhereAliased(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector
            ->load('comments', ['method' => JoinableLoader::INLOAD])
            ->where('user.id', 1)
            ->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
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
                    [
                        'id' => 3,
                        'user_id' => 1,
                        'message' => 'msg 3',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testAccessRelated(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        Assert::instanceOf($a->comments, Collection::class);
        Assert::instanceOf($b->comments, Collection::class);

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        Assert::equals($a->comments[0]->message, 'msg 1');
        Assert::equals($a->comments[1]->message, 'msg 2');
        Assert::equals($a->comments[2]->message, 'msg 3');
    }

    #[Test]
    public function testFilterByRelated(): void
    {
        $selector = new Select($this->orm, User::class);

        $all = $selector
            ->with('comments')
            ->where('comments.message', 'msg 3')
            ->fetchAll();

        Assert::count($all, 1);
        Assert::equals($all[0]->id, 1);
    }

    #[Test]
    public function testFilterByRelatedExpression(): void
    {
        $selector = new Select($this->orm, User::class);

        $all = $selector
            ->with('comments')
            ->where(
                'comments.id',
                new Expression($selector->getBuilder()->resolve('user.id')),
            )->fetchAll();

        Assert::count($all, 1);
        Assert::equals($all[0]->id, 1);
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        QueryAssert::writes(0, function () use ($a, $b): void {
            $tr = new Transaction($this->orm);
            $tr->persist($a);
            $tr->persist($b);
            $tr->run();
        });
    }

    #[Test]
    public function testCreateWithRelations(): void
    {
        $e = new User();
        $e->email = 'test@email.com';
        $e->balance = 300;
        $e->comments->add(new Comment());
        $e->comments->add(new Comment());

        $e->comments[0]->message = 'msg A';
        $e->comments[1]->message = 'msg B';

        QueryAssert::writes(3, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        // consecutive test
        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        Assert::equals($e->id, 3);

        Assert::true($this->orm->getHeap()->has($e));
        Assert::same($this->orm->getHeap()->get($e)->getStatus(), Node::MANAGED);

        Assert::true($this->orm->getHeap()->has($e->comments[0]));
        Assert::same($this->orm->getHeap()->get($e->comments[0])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->comments[0])->getData()['user_id'], $e->id);

        Assert::true($this->orm->getHeap()->has($e->comments[1]));
        Assert::same($this->orm->getHeap()->get($e->comments[1])->getStatus(), Node::MANAGED);
        Assert::same($this->orm->getHeap()->get($e->comments[1])->getData()['user_id'], $e->id);

        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        Assert::equals($selector->wherePK(3)->fetchData(), [
            [
                'id' => 3,
                'email' => 'test@email.com',
                'balance' => 300.0,
                'comments' => [
                    [
                        'id' => 4,
                        'user_id' => 3,
                        'message' => 'msg A',
                    ],
                    [
                        'id' => 5,
                        'user_id' => 3,
                        'message' => 'msg B',
                    ],
                ],
            ],
        ]);
    }

    #[Test]
    public function testRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->orderBy('user.id')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $tr = new Transaction($this->orm);
        $tr->persist($e);
        $tr->run();

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->orderBy('user.id_int')->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        Assert::count($e->comments, 2);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
    }

    #[Test]
    public function testAddAndRemoveChildren(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        $e->comments->remove(1);

        $c = new Comment();
        $c->message = 'msg 4';
        $e->comments->add($c);

        QueryAssert::writes(2, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        // consecutive test
        QueryAssert::writes(0, function () use (&$tr, $e): void {
            $tr = new Transaction($this->orm);
            $tr->persist($e);
            $tr->run();
        });

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);
        $selector->load('comments');

        /** @var User $e */
        $e = $selector->wherePK(1)->fetchOne();

        Assert::count($e->comments, 3);

        Assert::same($e->comments[0]->message, 'msg 1');
        Assert::same($e->comments[1]->message, 'msg 3');
        Assert::same($e->comments[2]->message, 'msg 4');
    }

    #[Test]
    public function testSliceAndSaveToAnotherParent(): void
    {
        $selector = new Select($this->orm, User::class);
        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments')->orderBy('user.id')->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        $b->comments = $a->comments->slice(0, 2);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }

        $b->comments[0]->message = 'new b';

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        QueryAssert::writes(2, function () use (&$tr, $a, $b): void {
            $tr = new Transaction($this->orm);
            $tr->persist($a);
            $tr->persist($b);
            $tr->run();
        });

        // consecutive
        QueryAssert::writes(0, function () use (&$tr, $a, $b): void {
            $tr = new Transaction($this->orm);
            $tr->persist($a);
            $tr->persist($b);
            $tr->run();
        });

        $selector = new Select($this->orm->withHeap(new Heap()), User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('comments', [
            'method' => JoinableLoader::INLOAD,
            'as' => 'comment',
        ])->orderBy('user.id_int')->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 2);

        Assert::equals($a->comments[0]->id, 3);
        Assert::equals($b->comments[0]->id, 1);
        Assert::equals($b->comments[1]->id, 2);

        Assert::equals($b->comments[0]->message, 'new b');
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id_int' => 'primary',
            'email_str' => 'string',
            'balance_float' => 'float',
        ]);

        $this->makeTable('comment', [
            'id_int' => 'primary',
            'user_id_int' => 'integer',
            'message_str' => 'string',
        ]);

        $this->makeFK('comment', 'user_id_int', 'user', 'id_int');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email_str', 'balance_float'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id_int', 'message_str'],
            [
                [1, 'msg 1'],
                [1, 'msg 2'],
                [1, 'msg 3'],
            ],
        );

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id' => 'id_int', 'email' => 'email_str', 'balance' => 'balance_float'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
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
                Schema::COLUMNS => ['id' => 'id_int', 'user_id' => 'user_id_int', 'message' => 'message_str'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
                Schema::SCOPE => SortByIDScope::class,
            ],
        ]));
    }
}
