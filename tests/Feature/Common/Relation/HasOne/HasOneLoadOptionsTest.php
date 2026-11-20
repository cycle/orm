<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasOne;

use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Select\Options\HasOneLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasOneLoadOptionsTest extends OrmTestCase
{
    #[Test]
    public function testLoadWithSingleQueryMethod(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadWithOuterQueryMethod(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            method: LoadMethod::OuterQuery,
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadWithCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('firstComment', new HasOneLoadOptions(
            where: ['@.level' => 1],
        ))->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadWithCustomWhereAndSingleQuery(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('firstComment', new HasOneLoadOptions(
            method: LoadMethod::SingleQuery,
            where: ['@.level' => 1],
        ))->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testNullWhereKeepsRelationWhereInJoinCondition(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => 4]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->load('firstComment', ['where' => null])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::null($b->firstComment);
    }

    #[Test]
    public function testNullOrderByKeepsRelationOrderBy(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))
            ->load('firstComment', ['orderBy' => null])
            ->orderBy('user.id')
            ->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testLoadWithOrderBy(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            orderBy: ['@.level' => 'ASC'],
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadWithScopeDisabled(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => 4]),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            scope: false,
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadWithCustomScope(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            scope: new Select\QueryScope(['@.level' => ['>=' => 3]], ['@.level' => 'DESC']),
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testLoadWithSingleQueryAndScopeAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // Array-based call as reference
        $expected = (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();

        // DTO-based call
        $res = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            method: LoadMethod::SingleQuery,
        ))->orderBy('user.id', 'DESC')->fetchAll();

        Assert::count($res, 2);
        Assert::same($res[0]->email, $expected[0]->email);
        Assert::same($res[1]->email, $expected[1]->email);
        Assert::same($res[0]->firstComment->message, $expected[0]->firstComment->message);
        Assert::same($res[1]->firstComment->message, $expected[1]->firstComment->message);
    }

    #[Test]
    public function testLoadWithDefaultOptions(): void
    {
        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions())
            ->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testLoadFromArchiveTable(): void
    {
        $this->makeTable('comment_archive', [
            'id' => 'primary',
            'user_id' => 'integer',
            'level' => 'integer',
            'message' => 'string',
        ]);

        $this->getDatabase()->table('comment_archive')->insertMultiple(
            ['user_id', 'level', 'message'],
            [
                [1, 1, 'archived 1'],
                [2, 1, 'archived 2.1'],
            ],
        );

        $this->orm = $this->withCommentsSchema([]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', new HasOneLoadOptions(
            table: 'comment_archive',
        ))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'archived 1');
        Assert::same($b->firstComment->message, 'archived 2.1');
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
                    'firstComment' => [
                        Relation::TYPE => Relation::HAS_ONE,
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
