<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Relation\BulkLoader;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Transaction;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Doctrine\Common\Collections\Collection;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class MorphedHasManyRelationTest extends OrmTestCase
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
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'first comment',
                    ],

                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'second comment',
                    ],

                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'third comment',
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
    public function testFetchAnother(): void
    {
        $selector = new Select($this->orm, Post::class);
        $selector->load('comments')->orderBy('post.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'user_id' => 1,
                'title' => 'post 1',
                'content' => 'post 1 body',
                'comments' => [
                    [
                        'id' => 4,
                        'parent_id' => 1,
                        'parent_type' => 'post',
                        'message' => 'post 1 comment',
                    ],
                    [
                        'id' => 6,
                        'parent_id' => 1,
                        'parent_type' => 'post',
                        'message' => 'post 1.1 comment',
                    ],
                ],
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'title' => 'post 2',
                'content' => 'post 2 body',
                'comments' => [
                    [
                        'id' => 5,
                        'parent_id' => 2,
                        'parent_type' => 'post',
                        'message' => 'post 2 comment',
                    ],
                    [
                        'id' => 7,
                        'parent_id' => 2,
                        'parent_type' => 'post',
                        'message' => 'post 2.1 comment',
                    ],
                ],
            ],
            [
                'id' => 3,
                'user_id' => 2,
                'title' => 'post 3',
                'content' => 'post 3 body',
                'comments' => [],
            ],
            [
                'id' => 4,
                'user_id' => 2,
                'title' => 'post 4',
                'content' => 'post 4 body',
                'comments' => [],
            ],
        ]);
    }

    #[Test]
    public function testLoadOverlapping(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector
            ->load('posts.comments')
            ->load('comments')
            ->orderBy('user.id');

        Assert::equals($selector->fetchData(), [
            [
                'id' => 1,
                'email' => 'hello@world.com',
                'balance' => 100.0,
                'posts' => [
                    [
                        'id' => 1,
                        'user_id' => 1,
                        'title' => 'post 1',
                        'content' => 'post 1 body',
                        'comments' => [
                            [
                                'id' => 4,
                                'parent_id' => 1,
                                'parent_type' => 'post',
                                'message' => 'post 1 comment',
                            ],
                            [
                                'id' => 6,
                                'parent_id' => 1,
                                'parent_type' => 'post',
                                'message' => 'post 1.1 comment',
                            ],
                        ],
                    ],
                    [
                        'id' => 2,
                        'user_id' => 1,
                        'title' => 'post 2',
                        'content' => 'post 2 body',
                        'comments' => [
                            [
                                'id' => 5,
                                'parent_id' => 2,
                                'parent_type' => 'post',
                                'message' => 'post 2 comment',
                            ],
                            [
                                'id' => 7,
                                'parent_id' => 2,
                                'parent_type' => 'post',
                                'message' => 'post 2.1 comment',
                            ],
                        ],
                    ],
                ],
                'comments' => [
                    [
                        'id' => 1,
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'first comment',
                    ],
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'second comment',
                    ],
                    [
                        'id' => 3,
                        'parent_id' => 1,
                        'parent_type' => 'user',
                        'message' => 'third comment',
                    ],
                ],
            ],
            [
                'id' => 2,
                'email' => 'another@world.com',
                'balance' => 200.0,
                'posts' => [
                    [
                        'id' => 3,
                        'user_id' => 2,
                        'title' => 'post 3',
                        'content' => 'post 3 body',
                        'comments' => [],
                    ],
                    [
                        'id' => 4,
                        'user_id' => 2,
                        'title' => 'post 4',
                        'content' => 'post 4 body',
                        'comments' => [],
                    ],
                ],
                'comments' => [],
            ],
        ]);
    }

    #[Test]
    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::instanceOf($a->comments, Collection::class);
        Assert::instanceOf($b->comments, Collection::class);

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 0);

        Assert::same($a->comments[0]->message, 'first comment');
        Assert::same($a->comments[1]->message, 'second comment');
        Assert::same($a->comments[2]->message, 'third comment');
    }

    #[Test]
    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        QueryAssert::writes(0, function () use ($a, $b): void {
            $tr = new Transaction($this->orm);
            $tr->persist($a);
            $tr->persist($b);
            $tr->run();
        });
    }

    #[Test]
    public function testDeleteComment(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $a->comments->remove(0);

        QueryAssert::writes(1, function () use (&$tr, $a, $b): void {
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

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::count($a->comments, 2);
        Assert::count($b->comments, 0);
    }

    #[Test]
    public function testMoveToAnotherParent(): void
    {
        /**
         * @var User $a
         * @var User $b
         */
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $b->comments = $a->comments->slice(0, 1);
        foreach ($b->comments as $c) {
            $a->comments->removeElement($c);
        }
        QueryAssert::writes(1, function () use (&$tr, $a, $b): void {
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

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        Assert::count($a->comments, 2);
        Assert::count($b->comments, 1);

        Assert::same($b->comments[0]->message, 'first comment');
        Assert::same($a->comments[0]->message, 'second comment');
        Assert::same($a->comments[1]->message, 'third comment');
    }

    #[Test]
    public function testMoveToAnother(): void
    {
        /**
         * @var User $a
         * @var Post $b
         */
        $a = (new Select($this->orm, User::class))->wherePK(1)->load('comments')->fetchOne();
        $b = (new Select($this->orm, Post::class))->wherePK(1)->load('comments')->fetchOne();

        $b->comments->add($a->comments[0]);
        $a->comments->removeElement($a->comments[0]);

        QueryAssert::writes(1, function () use (&$tr, $a, $b): void {
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

        $this->orm = $this->orm->withHeap(new Heap());
        $a = (new Select($this->orm, User::class))->wherePK(1)->load('comments')->fetchOne();
        $b = (new Select($this->orm, Post::class))->wherePK(1)->load('comments')->fetchOne();

        Assert::count($a->comments, 2);
        Assert::count($b->comments, 3);

        Assert::true($a->comments->exists(function ($i, $c) {
            return $c->message == 'third comment';
        }));

        Assert::true($a->comments->exists(function ($i, $c) {
            return $c->message == 'second comment';
        }));

        Assert::true($b->comments->exists(function ($i, $c) {
            return $c->message == 'first comment';
        }));

        Assert::true($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1 comment';
        }));

        Assert::true($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1.1 comment';
        }));
    }

    #[Test]
    public function testUpdateRelationSortedById(): void
    {
        $users = QueryAssert::reads(1, fn() => (new Select($this->orm, User::class))->fetchAll());

        QueryAssert::reads(1, fn() => (new BulkLoader($this->orm))->collect(...$users)
            ->load('comments', [
                'orderBy' => ['id' => 'DESC'],
            ])->run());
        QueryAssert::reads(0, function () use ($users): void {
            $checked = false;
            foreach ($users as $user) {
                Assert::iterable($user->comments);
                // Check order
                if (\count($user->comments) > 1) {
                    Assert::numeric($user->comments->offsetGet(0)->id)->greaterThan($user->comments->offsetGet(1)->id);
                    $checked = true;
                }
            }
            Assert::true($checked, 'No comments to check order.');
        });
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->makeTable('post', [
            'id' => 'primary',
            'user_id' => 'integer,nullable',
            'title' => 'string',
            'content' => 'string',
        ]);

        $this->getDatabase()->table('post')->insertMultiple(
            ['title', 'user_id', 'content'],
            [
                ['post 1', 1, 'post 1 body'],
                ['post 2', 1, 'post 2 body'],
                ['post 3', 2, 'post 3 body'],
                ['post 4', 2, 'post 4 body'],
            ],
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'parent_id' => 'integer',
            'parent_type' => 'string',
            'message' => 'string',
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['parent_id', 'parent_type', 'message'],
            [
                [1, 'user', 'first comment'],
                [1, 'user', 'second comment'],
                [1, 'user', 'third comment'],

                [1, 'post', 'post 1 comment'],
                [2, 'post', 'post 2 comment'],
                [1, 'post', 'post 1.1 comment'],
                [2, 'post', 'post 2.1 comment'],
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
                        Relation::TYPE => Relation::MORPHED_HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ],
                    ],
                    'posts' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Post::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ],
                    ],
                ],
            ],
            Post::class => [
                Schema::ROLE => 'post',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'title', 'content'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'comments' => [
                        Relation::TYPE => Relation::MORPHED_HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
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
                Schema::COLUMNS => ['id', 'parent_id', 'parent_type', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
