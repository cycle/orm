<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Relation\Morphed;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\BaseTest;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Traits\TableTrait;
use Cycle\ORM\Transaction;
use Doctrine\Common\Collections\Collection;

abstract class MorphedHasManyRelationTest extends BaseTest
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

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ]
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
            ]
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

    public function testFetchRelation(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testFetchAnother(): void
    {
        $selector = new Select($this->orm, Post::class);
        $selector->load('comments')->orderBy('post.id');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testLoadOverlapping(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector
            ->load('posts.comments')
            ->load('comments')
            ->orderBy('user.id');

        $this->assertEquals([
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
        ], $selector->fetchData());
    }

    public function testAccessEntity(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertInstanceOf(Collection::class, $a->comments);
        $this->assertInstanceOf(Collection::class, $b->comments);

        $this->assertCount(3, $a->comments);
        $this->assertCount(0, $b->comments);

        $this->assertSame('first comment', $a->comments[0]->message);
        $this->assertSame('second comment', $a->comments[1]->message);
        $this->assertSame('third comment', $a->comments[2]->message);
    }

    public function testNoWrite(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);
    }

    public function testDeleteComment(): void
    {
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $a->comments->remove(0);

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertCount(2, $a->comments);
        $this->assertCount(0, $b->comments);
    }

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

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, User::class);
        $selector->load('comments')->orderBy('user.id');
        [$a, $b] = $selector->fetchAll();

        $this->assertCount(2, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('first comment', $b->comments[0]->message);
        $this->assertSame('second comment', $a->comments[0]->message);
        $this->assertSame('third comment', $a->comments[1]->message);
    }

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

        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(1);

        // consecutive
        $this->captureWriteQueries();
        $tr = new Transaction($this->orm);
        $tr->persist($a);
        $tr->persist($b);
        $tr->run();
        $this->assertNumWrites(0);

        $this->orm = $this->orm->withHeap(new Heap());
        $a = (new Select($this->orm, User::class))->wherePK(1)->load('comments')->fetchOne();
        $b = (new Select($this->orm, Post::class))->wherePK(1)->load('comments')->fetchOne();

        $this->assertCount(2, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertTrue($a->comments->exists(function ($i, $c) {
            return $c->message == 'third comment';
        }));

        $this->assertTrue($a->comments->exists(function ($i, $c) {
            return $c->message == 'second comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'first comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1 comment';
        }));

        $this->assertTrue($b->comments->exists(function ($i, $c) {
            return $c->message == 'post 1.1 comment';
        }));
    }
}
