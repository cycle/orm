<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\Morphed;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\MorphedHasManyLoadOptions;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\Post;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;

class MorphedHasManyLoadOptionsTest extends OrmTestCase
{

    #[\Testo\Test]
    public function testLoadWithSingleQueryMethod(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($res, \count($expected));
        \Testo\Assert::count($res[0]->comments, \count($expected[0]->comments));
        \Testo\Assert::count($res[1]->comments, \count($expected[1]->comments));

        foreach ($expected[0]->comments as $i => $comment) {
            \Testo\Assert::same($res[0]->comments[$i]->message, $comment->message);
        }
        foreach ($expected[1]->comments as $i => $comment) {
            \Testo\Assert::same($res[1]->comments[$i]->message, $comment->message);
        }
    }

    #[\Testo\Test]
    public function testLoadWithOuterQueryMethod(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->comments, 4);
        \Testo\Assert::count($b->comments, 3);

        \Testo\Assert::same($a->comments[0]->message, 'msg 1');
        \Testo\Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[\Testo\Test]
    public function testLoadWithCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', new MorphedHasManyLoadOptions(
            where: ['@.level' => 1],
        ))->fetchAll();

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 1);

        \Testo\Assert::same($a->comments[0]->message, 'msg 1');
        \Testo\Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[\Testo\Test]
    public function testLoadWithCustomWhereAndSingleQuery(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::WHERE => ['@.level' => ['>=' => 2]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', new MorphedHasManyLoadOptions(
            method: LoadMethod::SingleQuery,
            where: ['@.level' => 1],
        ))->fetchAll();

        \Testo\Assert::count($a->comments, 1);
        \Testo\Assert::count($b->comments, 1);

        \Testo\Assert::same($a->comments[0]->message, 'msg 1');
        \Testo\Assert::same($b->comments[0]->message, 'msg 2.1');
    }

    #[\Testo\Test]
    public function testLoadWithScopeDisabled(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => 4]),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            scope: false,
        ))->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->comments, 4);
        \Testo\Assert::count($b->comments, 3);
    }

    #[\Testo\Test]
    public function testLoadWithCustomScope(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        $res = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']),
        ))->orderBy('user.id')->fetchAll();

        [$a, $b] = $res;

        \Testo\Assert::count($a->comments, 2);
        \Testo\Assert::count($b->comments, 1);

        \Testo\Assert::same($a->comments[0]->message, 'msg 4');
        \Testo\Assert::same($a->comments[1]->message, 'msg 3');
        \Testo\Assert::same($b->comments[0]->message, 'msg 2.3');
    }

    #[\Testo\Test]
    public function testLoadWithSingleQueryAndScopeAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::WHERE => ['@.level' => ['>=' => 3]],
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id', 'DESC')->fetchAll();

        \Testo\Assert::count($res, \count($expected));
        \Testo\Assert::same($res[0]->email, $expected[0]->email);
        \Testo\Assert::same($res[1]->email, $expected[1]->email);

        \Testo\Assert::count($res[1]->comments, \count($expected[1]->comments));
        \Testo\Assert::count($res[0]->comments, \count($expected[0]->comments));

        foreach ($expected[1]->comments as $i => $comment) {
            \Testo\Assert::same($res[1]->comments[$i]->message, $comment->message);
        }
        foreach ($expected[0]->comments as $i => $comment) {
            \Testo\Assert::same($res[0]->comments[$i]->message, $comment->message);
        }
    }

    #[\Testo\Test]
    public function testLoadWithDefaultOptions(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions())
            ->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->comments, 4);
        \Testo\Assert::count($b->comments, 3);
    }

    #[\Testo\Test]
    public function testLoadFromArchiveTable(): void
    {
        $this->makeTable('comment_archive', [
            'id' => 'primary',
            'parent_id' => 'integer',
            'parent_type' => 'string',
            'level' => 'int',
            'message' => 'string',
        ]);

        $this->getDatabase()->table('comment_archive')->insertMultiple(
            ['parent_id', 'parent_type', 'level', 'message'],
            [
                [1, 'user', 1, 'archived 1'],
                [1, 'user', 2, 'archived 2'],
                [2, 'user', 1, 'archived 2.1'],
            ],
        );

        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new MorphedHasManyLoadOptions(
            table: 'comment_archive',
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($a->comments, 2);
        \Testo\Assert::count($b->comments, 1);

        \Testo\Assert::same($a->comments[0]->message, 'archived 1');
        \Testo\Assert::same($a->comments[1]->message, 'archived 2');
        \Testo\Assert::same($b->comments[0]->message, 'archived 2.1');
    }

    #[\Testo\Lifecycle\BeforeTest]
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
            ['parent_id', 'parent_type', 'level', 'message'],
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
