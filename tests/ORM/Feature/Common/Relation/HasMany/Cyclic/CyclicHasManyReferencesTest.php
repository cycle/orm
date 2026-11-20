<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasMany\Cyclic;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\CyclicRef\Comment;
use Cycle\ORM\Tests\Fixtures\CyclicRef\Post;
use Cycle\ORM\Tests\Fixtures\CyclicRef\TimestampedMapper;
use Cycle\ORM\Tests\Fixtures\CyclicRef\User;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class CyclicHasManyReferencesTest extends OrmTestCase
{
    #[Test]
    public function testCreate(): void
    {
        $u = new User();
        $u->email = 'test@email.com';

        $p = new Post();
        $p->title = 'Title';
        $p->content = 'Hello World';

        $c = new Comment();
        $c->user = $u;
        $c->message = 'hello hello';

        $p->addComment($c);

        QueryAssert::writes(4, fn() => $this->save($p));

        // no changes!
        QueryAssert::writes(0, fn() => $this->save($u));

        $this->orm = $this->orm->withHeap(new Heap());
        $selector = new Select($this->orm, Post::class);
        $selector->load('lastComment.user')
            ->load('comments.user');

        $p1 = $selector->wherePK(1)->fetchOne();

        Assert::equals($p1->id, $p->id);
        Assert::equals($p1->lastComment->id, $p->lastComment->id);

        Assert::equals($p1->lastComment->user->id, $p->lastComment->user->id);
        Assert::equals($p1->comments[0]->id, $p->comments[0]->id);
        Assert::equals($p1->comments[0]->post_id, $p1->id);
    }

    #[Test]
    public function testReferenceUpdate(): void
    {
        $u1 = new User();
        $u1->email = 'test@email.com';

        $u2 = new User();
        $u2->email = 'test2@email.com';

        $p = new Post();
        $p->title = 'Title';
        $p->content = 'Hello World';

        $c1 = new Comment();
        $c1->user = $u1;
        $c1->message = 'hello hello';

        $c2 = new Comment();
        $c2->user = $u2;
        $c2->message = 'hi hi';

        $p->addComment($c1);
        $p->addComment($c2);

        QueryAssert::writes(6, fn() => $this->save($p));

        $c3 = new Comment();
        $c3->user = $u2;
        $c3->message = 'hello again 2';

        $p->addComment($c3);

        QueryAssert::writes(2, fn() => $this->save($p));

        $this->orm = $this->orm->withHeap(new Heap());

        $p1 = (new Select($this->orm, Post::class))
            ->load('lastComment.user')
            ->load('comments.user')
            ->wherePK($p->id)->fetchOne();

        $pCommentIds = array_column($p->comments->toArray(), 'id');
        $p1CommentIds = array_column($p1->comments->toArray(), 'id');
        sort($pCommentIds);
        sort($p1CommentIds);

        Assert::equals($c3->id, $p1->lastComment->id);
        Assert::equals($p1->lastComment->user->id, $p->lastComment->user->id);
        Assert::equals($p1CommentIds, $pCommentIds);
        Assert::equals($p1->comments[0]->post_id, $p1->id);
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('post', [
            'id' => 'primary',
            'title' => 'string',
            'content' => 'string',
            'last_comment_id' => 'integer,nullable',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->makeTable('comment', [
            'id' => 'primary',
            'post_id' => 'integer',
            'user_id' => 'integer',
            'message' => 'string',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ]);

        $this->orm = $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'created_at', 'updated_at'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'post_id', 'message', 'created_at', 'updated_at'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'user' => [
                        Relation::TYPE => Relation::BELONGS_TO,
                        Relation::TARGET => User::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'user_id',
                            Relation::OUTER_KEY => 'id',
                        ],
                    ],
                ],
            ],
            Post::class => [
                Schema::ROLE => 'post',
                Schema::MAPPER => TimestampedMapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'post',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'title', 'content', 'last_comment_id', 'created_at', 'updated_at'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'lastComment' => [
                        Relation::TYPE => Relation::REFERS_TO,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'last_comment_id',
                            Relation::OUTER_KEY => 'id',
                            Relation::NULLABLE => true,
                        ],
                    ],
                    'comments' => [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'post_id',
                        ],
                    ],
                ],
            ],
        ]));
    }
}
