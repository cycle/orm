<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\Database\Exception\StatementException;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class MorphedHasManyScopeTest extends OrmTestCase
{
    #[Test]
    public function testOrdered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[0]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[2]->message, 'msg 2');
        Assert::same($a->comments[3]->message, 'msg 1');

        Assert::same($b->comments[0]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
        Assert::same($b->comments[2]->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderedASC(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'msg 4');
        Assert::same($a->comments[2]->message, 'msg 3');
        Assert::same($a->comments[1]->message, 'msg 2');
        Assert::same($a->comments[0]->message, 'msg 1');

        Assert::same($b->comments[2]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
        Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderedPosts(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[0]->message, 'p.msg 4');
        Assert::same($a->comments[1]->message, 'p.msg 3');
        Assert::same($a->comments[2]->message, 'p.msg 2');
        Assert::same($a->comments[3]->message, 'p.msg 1');

        Assert::same($b->comments[0]->message, 'p.msg 2.3');
        Assert::same($b->comments[1]->message, 'p.msg 2.2');
        Assert::same($b->comments[2]->message, 'p.msg 2.1');
    }

    #[Test]
    public function testOrderedPostsASC(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'p.msg 4');
        Assert::same($a->comments[2]->message, 'p.msg 3');
        Assert::same($a->comments[1]->message, 'p.msg 2');
        Assert::same($a->comments[0]->message, 'p.msg 1');

        Assert::same($b->comments[2]->message, 'p.msg 2.3');
        Assert::same($b->comments[1]->message, 'p.msg 2.2');
        Assert::same($b->comments[0]->message, 'p.msg 2.1');
    }

    #[Test]
    public function testOrderedInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[0]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[2]->message, 'msg 2');
        Assert::same($a->comments[3]->message, 'msg 1');

        Assert::same($b->comments[0]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
        Assert::same($b->comments[2]->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderedASCInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'msg 4');
        Assert::same($a->comments[2]->message, 'msg 3');
        Assert::same($a->comments[1]->message, 'msg 2');
        Assert::same($a->comments[0]->message, 'msg 1');

        Assert::same($b->comments[2]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
        Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderedPostsInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('post.id', 'ASC')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[0]->message, 'p.msg 4');
        Assert::same($a->comments[1]->message, 'p.msg 3');
        Assert::same($a->comments[2]->message, 'p.msg 2');
        Assert::same($a->comments[3]->message, 'p.msg 1');

        Assert::same($b->comments[0]->message, 'p.msg 2.3');
        Assert::same($b->comments[1]->message, 'p.msg 2.2');
        Assert::same($b->comments[2]->message, 'p.msg 2.1');
    }

    #[Test]
    public function testOrderedPostsASCInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('post.id', 'ASC')->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'p.msg 4');
        Assert::same($a->comments[2]->message, 'p.msg 3');
        Assert::same($a->comments[1]->message, 'p.msg 2');
        Assert::same($a->comments[0]->message, 'p.msg 1');

        Assert::same($b->comments[2]->message, 'p.msg 2.3');
        Assert::same($b->comments[1]->message, 'p.msg 2.2');
        Assert::same($b->comments[0]->message, 'p.msg 2.1');
    }

    #[Test]
    public function testOrderedPromisedASC(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'msg 4');
        Assert::same($a->comments[2]->message, 'msg 3');
        Assert::same($a->comments[1]->message, 'msg 2');
        Assert::same($a->comments[0]->message, 'msg 1');

        Assert::same($b->comments[2]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
        Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderedAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[2]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[0]->message, 'msg 2');
        Assert::same($b->comments[1]->message, 'msg 2.3');
        Assert::same($b->comments[0]->message, 'msg 2.2');
    }

    #[Test]
    public function testOrderedAndWherePromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[2]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[0]->message, 'msg 2');
        Assert::same($b->comments[1]->message, 'msg 2.3');
        Assert::same($b->comments[0]->message, 'msg 2.2');
    }

    #[Test]
    public function testPostOrderedPromisedASC(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->fetchAll();

        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        Assert::same($a->comments[3]->message, 'p.msg 4');
        Assert::same($a->comments[2]->message, 'p.msg 3');
        Assert::same($a->comments[1]->message, 'p.msg 2');
        Assert::same($a->comments[0]->message, 'p.msg 1');

        Assert::same($b->comments[2]->message, 'p.msg 2.3');
        Assert::same($b->comments[1]->message, 'p.msg 2.2');
        Assert::same($b->comments[0]->message, 'p.msg 2.1');
    }

    #[Test]
    public function testPostOrderedAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[2]->message, 'p.msg 4');
        Assert::same($a->comments[1]->message, 'p.msg 3');
        Assert::same($a->comments[0]->message, 'p.msg 2');
        Assert::same($b->comments[1]->message, 'p.msg 2.3');
        Assert::same($b->comments[0]->message, 'p.msg 2.2');
    }

    #[Test]
    public function testPostOrderedAndWherePromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, Post::class))->fetchAll();

        $this->captureReadQueries();
        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);
        $this->assertNumReads(2);

        Assert::same($a->comments[2]->message, 'p.msg 4');
        Assert::same($a->comments[1]->message, 'p.msg 3');
        Assert::same($a->comments[0]->message, 'p.msg 2');
        Assert::same($b->comments[1]->message, 'p.msg 2.3');
        Assert::same($b->comments[0]->message, 'p.msg 2.2');
    }

    #[Test]
    public function testOrderedAndWhereReversed(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments')->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[0]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[2]->message, 'msg 2');
        Assert::same($b->comments[0]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
    }

    #[Test]
    public function testOrderedAndWhereReversedInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[0]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[2]->message, 'msg 2');
        Assert::same($b->comments[0]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
    }

    #[Test]
    public function testOrderedAndWhereReversedPromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::count($a->comments, 3);
        Assert::count($b->comments, 2);

        Assert::same($a->comments[0]->message, 'msg 4');
        Assert::same($a->comments[1]->message, 'msg 3');
        Assert::same($a->comments[2]->message, 'msg 2');
        Assert::same($b->comments[0]->message, 'msg 2.3');
        Assert::same($b->comments[1]->message, 'msg 2.2');
    }

    #[Test]
    public function testCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        // overwrites default one
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', [
            'where' => ['@.level' => 1],
        ])->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 1);

        Assert::same($a->comments[0]->message, 'msg 1');
        Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[Test]
    public function testCustomWhereInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        // overwrites default one
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', [
            'where' => ['@.level' => 1],
            'method' => Select\JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 1);

        Assert::same($a->comments[0]->message, 'msg 1');
        Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[Test]
    public function testWithWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => 4],
        ]);

        // second user has been filtered out
        $res = (new Select($this->orm, User::class))->with('comments')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'hello@world.com');
    }

    #[Test]
    public function testWithWhereAltered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => 4],
        ]);

        // second user has been filtered out
        $res = (new Select($this->orm, User::class))->with('comments', [
            'where' => ['@.level' => 1],
        ])->orderBy('user.id')->fetchAll();

        Assert::count($res, 2);
        Assert::same($res[0]->email, 'hello@world.com');
        Assert::same($res[1]->email, 'another@world.com');
    }

    #[Test]
    public function testLimitParentSelection(): void
    {
        $this->orm = $this->withCommentsSchema([
        ]);

        // second user has been filtered out
        $res = (new Select($this->orm, User::class))
            ->load('comments')
            ->limit(1)->orderBy('user.id')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'hello@world.com');
        Assert::count($res[0]->comments, 4);
    }

    #[Test]
    public function testLimitParentSelectionError(): void
    {
        Expect::exception(LoaderException::class);

        $this->orm = $this->withCommentsSchema([]);

        // do not allow limits with joined and loaded relations
        (new Select($this->orm, User::class))
            ->load('comments', ['method' => Select\JoinableLoader::INLOAD])
            ->limit(1)->orderBy('user.id')->fetchAll();
    }

    #[Test]
    public function testInloadWithOrderAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => ['>=' => 3]],
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        $res = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();

        Assert::count($res, 2);
        Assert::same($res[1]->email, 'hello@world.com');
        Assert::same($res[0]->email, 'another@world.com');

        Assert::count($res[1]->comments, 2);
        Assert::count($res[0]->comments, 1);

        Assert::same($res[1]->comments[0]->message, 'msg 4');
        Assert::same($res[1]->comments[1]->message, 'msg 3');
        Assert::same($res[0]->comments[0]->message, 'msg 2.3');
    }

    #[Test]
    public function testInvalidOrderBy(): void
    {
        Expect::exception(StatementException::class);

        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => ['>=' => 3]],
            Schema::SCOPE => new Select\QueryScope([], ['@.column' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();
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
            'level' => 'int',
            'message' => 'string',
        ]);

        $this->getDatabase()->table('comment')->insertMultiple(
            ['parent_id', 'parent_type', 'level', 'message',],
            [
                [1, 'user', 1, 'msg 1'],
                [1, 'user', 2, 'msg 2'],
                [1, 'user', 3, 'msg 3'],
                [1, 'user', 4, 'msg 4'],
                [2, 'user', 1, 'msg 2.1'],
                [2, 'user', 2, 'msg 2.2'],
                [2, 'user', 3, 'msg 2.3'],
                [1, 'post', 1, 'p.msg 1'],
                [1, 'post', 2, 'p.msg 2'],
                [1, 'post', 3, 'p.msg 3'],
                [1, 'post', 4, 'p.msg 4'],
                [2, 'post', 1, 'p.msg 2.1'],
                [2, 'post', 2, 'p.msg 2.2'],
                [2, 'post', 3, 'p.msg 2.3'],
            ],
        );
    }

    protected function withCommentsSchema(array $relationSchema)
    {
        $eSchema = [];
        $rSchema = [];

        if (isset($relationSchema[Schema::SCOPE])) {
            $eSchema[Schema::SCOPE] = $relationSchema[Schema::SCOPE];
        }

        if (isset($relationSchema[Relation::WHERE])) {
            $rSchema[Relation::WHERE] = $relationSchema[Relation::WHERE];
        }

        return $this->orm->withSchema(new Schema([
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
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ] + $rSchema,
                    ],
                ],
                Schema::SCOPE => SortByIDScope::class,
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
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'parent_id',
                            Relation::MORPH_KEY => 'parent_type',
                        ] + $rSchema,
                    ],
                ],
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'parent_id', 'parent_type', 'message', 'level'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ] + $eSchema,
        ]));
    }
}
