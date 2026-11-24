<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasOne;

use Cycle\Database\Exception\StatementException;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\JoinableLoader;
use Cycle\ORM\Tests\Fixtures\Comment;
use Cycle\ORM\Tests\Fixtures\SortByIDScope;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Core\Exception\SkipTest;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasOneScopeTest extends OrmTestCase
{
    #[Test]
    public function testScopeOrdered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');

        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testScopeOrderedAsc(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');

        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testScopeOrderedAscInLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();


        Assert::same($a->firstComment->message, 'msg 1');

        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testScopeOrderedPromisedAsc(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');

        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testScopeOrderedAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 2');
        Assert::same($b->firstComment->message, 'msg 2.2');
    }

    #[Test]
    public function testScopeOrderedAndWherePromised(): never
    {
        throw new SkipTest('Incomplete');
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::same($a->firstComment->message, 'msg 2');
        Assert::same($b->firstComment->message, 'msg 2.2');
    }

    #[Test]
    public function testScopeOrderedAndWhereReversed(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testScopeOrderedAndWhereReversedInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testScopeOrderedAndWhereReversedPromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');
        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testScopeOrderedAndCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        // overwrites default one
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('firstComment', [
            'where' => ['@.level' => 1],
        ])->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testScopeOrderedAndCustomWhereInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        // overwrites default one
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('firstComment', [
            'where' => ['@.level' => 1],
            'method' => JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');
        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testOrderByWithScopeOrdered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');

        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testWithOrderByInLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');

        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testWithOrderByLazyLoad(): never
    {
        throw new SkipTest('Incomplete');
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 4');

        Assert::same($b->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testWithOrderByAltered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment', [
            'orderBy' => ['@.level' => 'ASC'],
        ])->orderBy('user.id')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 1');

        Assert::same($b->firstComment->message, 'msg 2.1');
    }

    #[Test]
    public function testWithOrderByAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::WHERE => ['@.level' => ['>=' => 2]],
                Relation::ORDER_BY => ['@.level' => 'ASC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('firstComment')->fetchAll();

        Assert::same($a->firstComment->message, 'msg 2');
        Assert::same($b->firstComment->message, 'msg 2.2');
    }

    #[Test]
    public function testWithWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => 4]],
        ]);

        // second user has been filtered out
        $res = (new Select($this->orm, User::class))->with('firstComment')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'hello@world.com');
    }

    #[Test]
    public function testWithWhereAltered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => ['@.level' => 4],
        ]);

        // second user has been filtered out
        $res = (new Select($this->orm, User::class))->with('firstComment', [
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
            ->load('firstComment')
            ->limit(1)->orderBy('user.id')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'hello@world.com');
        Assert::equals($res[0]->firstComment->level, 1);
    }

    #[Test]
    public function testLimitParentSelectionError(): never
    {
        throw new SkipTest('Incomplete');
        Expect::exception(LoaderException::class);

        $this->orm = $this->withCommentsSchema([]);

        // do not allow limits with joined and loaded relations
        (new Select($this->orm, User::class))
            ->load('firstComment', ['method' => JoinableLoader::INLOAD])
            ->limit(1)->orderBy('user.id')->fetchAll();
    }

    #[Test]
    public function testInloadWithScopeOrderedAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        $res = (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id', 'DESC')->fetchAll();

        Assert::count($res, 2);
        Assert::same($res[1]->email, 'hello@world.com');
        Assert::same($res[0]->email, 'another@world.com');

        Assert::same($res[1]->firstComment->message, 'msg 4');
        Assert::same($res[0]->firstComment->message, 'msg 2.3');
    }

    #[Test]
    public function testInvalidOrderBy(): void
    {
        Expect::exception(StatementException::class);

        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
            Schema::SCOPE => new Select\QueryScope([], ['@.column' => 'DESC']),
        ]);

        // sort by users and then by comments and only include comments with level > 3
        (new Select($this->orm, User::class))->load('firstComment', [
            'method' => JoinableLoader::INLOAD,
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
