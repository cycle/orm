<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\Options\HasManyLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\ORM\Tests\Functional\Driver\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class HasManyLoadOptionsTest extends BaseTest
{
    use TableTrait;

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
        $res = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(\count($expected), $res);
        $this->assertCount(\count($expected[0]->comments), $res[0]->comments);
        $this->assertCount(\count($expected[1]->comments), $res[1]->comments);

        foreach ($expected[0]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[0]->comments[$i]->message);
        }
        foreach ($expected[1]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[1]->comments[$i]->message);
        }
    }

    public function testLoadWithOuterQueryMethod(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testLoadWithCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', new HasManyLoadOptions(
            where: ['@.level' => 1],
        ))->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testLoadWithCustomWhereAndSingleQuery(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
            where: ['@.level' => 1],
        ))->fetchAll();

        $this->assertCount(1, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
    }

    public function testLoadWithOrderBy(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);

        $this->assertSame('msg 1', $a->comments[0]->message);
        $this->assertSame('msg 4', $a->comments[3]->message);
        $this->assertSame('msg 2.1', $b->comments[0]->message);
        $this->assertSame('msg 2.3', $b->comments[2]->message);
    }

    public function testLoadWithScopeDisabled(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => 4]),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            scope: false,
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);
    }

    public function testLoadWithCustomScope(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        $res = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']),
        ))->orderBy('user.id')->fetchAll();

        [$a, $b] = $res;

        $this->assertCount(2, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('msg 4', $a->comments[0]->message);
        $this->assertSame('msg 3', $a->comments[1]->message);
        $this->assertSame('msg 2.3', $b->comments[0]->message);
    }

    public function testLoadWithCustomScopeAndSingleQuery(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
            'scope' => new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']),
        ])->orderBy('user.id')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']),
        ))->orderBy('user.id')->fetchAll();

        $this->assertCount(\count($expected), $res);
        $this->assertCount(\count($expected[0]->comments), $res[0]->comments);
        $this->assertCount(\count($expected[1]->comments), $res[1]->comments);

        foreach ($expected[0]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[0]->comments[$i]->message);
        }
        foreach ($expected[1]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[1]->comments[$i]->message);
        }
    }

    public function testLoadWithSingleQueryAndScopeAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('comments', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id', 'DESC')->fetchAll();

        $this->assertCount(\count($expected), $res);
        $this->assertSame($expected[0]->email, $res[0]->email);
        $this->assertSame($expected[1]->email, $res[1]->email);

        $this->assertCount(\count($expected[1]->comments), $res[1]->comments);
        $this->assertCount(\count($expected[0]->comments), $res[0]->comments);

        foreach ($expected[1]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[1]->comments[$i]->message);
        }
        foreach ($expected[0]->comments as $i => $comment) {
            $this->assertSame($comment->message, $res[0]->comments[$i]->message);
        }
    }

    public function testLoadWithDefaultOptions(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions())
            ->orderBy('user.id')->fetchAll();

        $this->assertCount(4, $a->comments);
        $this->assertCount(3, $b->comments);
    }

    public function testLoadFromArchiveTable(): void
    {
        // Create archive table with the same structure as comment
        $this->makeTable('comment_archive', [
            'id' => 'primary',
            'user_id' => 'integer',
            'level' => 'integer',
            'message' => 'string',
        ]);

        $this->getDatabase()->table('comment_archive')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 10, 'archived 1'],
                [1, 20, 'archived 2'],
                [2, 10, 'archived 2.1'],
            ],
        );

        $this->orm = $this->withCommentsSchema([]);

        // Load from archive table instead of default "comment" table
        [$a, $b] = (new Select($this->orm, User::class))->load('comments', new HasManyLoadOptions(
            table: 'comment_archive',
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        // Data should come from archive, not from the main comment table
        $this->assertCount(2, $a->comments);
        $this->assertCount(1, $b->comments);

        $this->assertSame('archived 1', $a->comments[0]->message);
        $this->assertSame('archived 2', $a->comments[1]->message);
        $this->assertSame('archived 2.1', $b->comments[0]->message);
    }

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
            ],
        );

        $this->makeTable('comment', [
            'id' => 'primary',
            'user_id' => 'integer',
            'level' => 'integer',
            'message' => 'string',
        ]);

        $this->makeFK('comment', 'user_id', 'user', 'id');

        $this->getDatabase()->table('comment')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 1, 'msg 1'],
                [1, 2, 'msg 2'],
                [1, 3, 'msg 3'],
                [1, 4, 'msg 4'],
                [2, 1, 'msg 2.1'],
                [2, 2, 'msg 2.2'],
                [2, 3, 'msg 2.3'],
            ],
        );
    }

    protected function withCommentsSchema(array $relationSchema): ORMInterface
    {
        $eSchema = [];
        if (isset($relationSchema[Schema::SCOPE])) {
            $eSchema[Schema::SCOPE] = $relationSchema[Schema::SCOPE];
        }

        $rSchema = $relationSchema[Relation::SCHEMA] ?? [];

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
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => Comment::class,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'user_id',
                        ] + $rSchema,
                    ],
                ],
                Schema::SCOPE => SortByIDScope::class,
            ],
            Comment::class => [
                Schema::ROLE => 'comment',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'comment',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'level', 'message'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ] + $eSchema,
        ]));
    }
}
