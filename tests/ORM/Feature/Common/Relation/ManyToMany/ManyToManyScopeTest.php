<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany;

use Cycle\Database\Exception\StatementException;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\Mapper\Mapper;
use Cycle\ORM\Relation;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Tests\Fixtures\Tag;
use Cycle\ORM\Tests\Fixtures\TagContext;
use Cycle\ORM\Tests\Fixtures\User;
use Cycle\Testing\Database\Assert\QueryAssert;
use Cycle\Testing\ORM\OrmTestCase;
use Testo\Assert;
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class ManyToManyScopeTest extends OrmTestCase
{
    #[Test]
    public function testScopeOrdered(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedDesc(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[3]->name, 'tag a');
        Assert::same($a->tags[2]->name, 'tag b');
        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedInload(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByAndScopeOrdered(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByAsc(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByDesc(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[3]->name, 'tag a');
        Assert::same($a->tags[2]->name, 'tag b');
        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByInload(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByPostload(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::POSTLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByPromisedASC(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'ASC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::count($a->tags, 4);
            Assert::count($b->tags, 3);
        });

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByAltered(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::ORDER_BY => ['@.level' => 'DESC']],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', ['orderBy' => ['@.level' => 'ASC']])->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedDESCInload(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 4);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[3]->name, 'tag a');
        Assert::same($a->tags[2]->name, 'tag b');
        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedPromisedASC(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::count($a->tags, 4);
            Assert::count($b->tags, 3);
        });

        Assert::same($a->tags[0]->name, 'tag a');
        Assert::same($a->tags[1]->name, 'tag b');
        Assert::same($a->tags[2]->name, 'tag d');
        Assert::same($a->tags[3]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedPromisedDESC(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::count($a->tags, 4);
            Assert::count($b->tags, 3);
        });

        Assert::same($a->tags[3]->name, 'tag a');
        Assert::same($a->tags[2]->name, 'tag b');
        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedAndWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag d');
        Assert::same($a->tags[1]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testWithOrderByAndWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [
                Relation::WHERE => ['@.level' => ['>=' => 3]],
                Relation::ORDER_BY => ['@.level' => 'ASC'],
            ],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag d');
        Assert::same($a->tags[1]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedDESCAndWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags')->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedAndWhereInload(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[0]->name, 'tag d');
        Assert::same($a->tags[1]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedDESCAndWhereInload(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 2);
        Assert::count($b->tags, 3);

        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testScopeOrderedAndWherePromise(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::count($a->tags, 2);
            Assert::count($b->tags, 3);
        });

        Assert::same($a->tags[0]->name, 'tag d');
        Assert::same($a->tags[1]->name, 'tag e');

        Assert::same($b->tags[0]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[2]->name, 'tag f');
    }

    #[Test]
    public function testOrderedDESCAndWherePromise(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->orderBy('user.id')->fetchAll();

        QueryAssert::reads(2, function () use ($a, $b): void {
            Assert::count($a->tags, 2);
            Assert::count($b->tags, 3);
        });

        Assert::same($a->tags[1]->name, 'tag d');
        Assert::same($a->tags[0]->name, 'tag e');

        Assert::same($b->tags[2]->name, 'tag c');
        Assert::same($b->tags[1]->name, 'tag d');
        Assert::same($b->tags[0]->name, 'tag f');
    }

    #[Test]
    public function testCustomWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a, $b] = $selector->load('tags', [
            'where' => ['@.level' => 1],
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 1);
        Assert::count($b->tags, 0);

        Assert::same($a->tags[0]->name, 'tag a');
    }

    #[Test]
    public function testCustomWhereInload(): void
    {
        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 3]]],
        ]);

        $selector = new Select($this->orm, User::class);

        /**
         * @var User $a
         * @var User $b
         */
        [$a] = $selector->load('tags', [
            'method' => Select\JoinableLoader::INLOAD,
            'where' => ['@.level' => 1],
        ])->orderBy('user.id')->fetchAll();

        Assert::count($a->tags, 1);

        Assert::same($a->tags[0]->name, 'tag a');
    }

    #[Test]
    public function testWithWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 6]]],
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'another@world.com');
    }

    #[Test]
    public function testWithWhereAltered(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::WHERE => ['@.level' => ['>=' => 6]],
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags', [
            'where' => ['@.level' => ['>=' => 5]],
        ])->orderBy('user.id')->fetchAll();

        Assert::count($res, 2);
        Assert::same($res[0]->email, 'hello@world.com');
        Assert::same($res[1]->email, 'another@world.com');
    }

    #[Test]
    public function testLimitParentSelection(): void
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags')
            ->limit(1)
            ->orderBy('user.id')->fetchAll();

        Assert::count($res, 1);
        Assert::same($res[0]->email, 'hello@world.com');
        Assert::count($res[0]->tags, 4);
    }

    #[Test]
    public function testLimitParentSelectionError(): void
    {
        Expect::exception(LoaderException::class);

        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags', ['method' => Select\JoinableLoader::INLOAD])
            ->limit(1)
            ->orderBy('user.id')->fetchAll();
    }

    #[Test]
    public function testInvalidOrderBy(): void
    {
        Expect::exception(StatementException::class);

        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.column' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();
    }

    #[BeforeTest]
    public function setUp(): void
    {
        $this->makeTable('user', [
            'id' => 'primary',
            'email' => 'string',
            'balance' => 'float',
        ]);

        $this->makeTable('tag', [
            'id' => 'primary',
            'level' => 'integer',
            'name' => 'string',
        ]);

        $this->makeTable('tag_user_map', [
            'id' => 'primary',
            'user_id' => 'integer',
            'tag_id' => 'integer',
            'as' => 'string,nullable',
        ]);

        $this->makeFK('tag_user_map', 'user_id', 'user', 'id');
        $this->makeFK('tag_user_map', 'tag_id', 'tag', 'id');

        $this->getDatabase()->table('user')->insertMultiple(
            ['email', 'balance'],
            [
                ['hello@world.com', 100],
                ['another@world.com', 200],
            ],
        );

        $this->getDatabase()->table('tag')->insertMultiple(
            ['name', 'level'],
            [
                ['tag a', 1],
                ['tag b', 2],
                ['tag c', 3],
                ['tag d', 4],
                ['tag e', 5],
                ['tag f', 6],
            ],
        );

        $this->getDatabase()->table('tag_user_map')->insertMultiple(
            ['user_id', 'tag_id'],
            [
                [1, 1],
                [1, 2],
                [2, 3],

                [1, 4],
                [1, 5],

                [2, 4],
                [2, 6],
            ],
        );
    }

    protected function withTagSchema(array $relationSchema)
    {
        $eSchema = [];
        if (isset($relationSchema[Schema::SCOPE])) {
            $eSchema[Schema::SCOPE] = $relationSchema[Schema::SCOPE];
        }

        $rSchema = $relationSchema[Relation::SCHEMA] ?? [];

        return $this->withSchema(new Schema([
            User::class => [
                Schema::ROLE => 'user',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'user',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'email', 'balance'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [
                    'tags' => [
                        Relation::TYPE => Relation::MANY_TO_MANY,
                        Relation::TARGET => Tag::class,
                        Relation::LOAD => Relation::LOAD_PROMISE,
                        Relation::SCHEMA => [
                            Relation::CASCADE => true,
                            Relation::THROUGH_ENTITY => TagContext::class,
                            Relation::INNER_KEY => 'id',
                            Relation::OUTER_KEY => 'id',
                            Relation::THROUGH_INNER_KEY => 'user_id',
                            Relation::THROUGH_OUTER_KEY => 'tag_id',
                        ] + $rSchema,
                    ],
                ],
            ],
            Tag::class => [
                Schema::ROLE => 'tag',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'name', 'level'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ] + $eSchema,
            TagContext::class => [
                Schema::ROLE => 'tag_context',
                Schema::MAPPER => Mapper::class,
                Schema::DATABASE => 'default',
                Schema::TABLE => 'tag_user_map',
                Schema::PRIMARY_KEY => 'id',
                Schema::COLUMNS => ['id', 'user_id', 'tag_id', 'as'],
                Schema::TYPECAST => ['id' => 'int', 'user_id' => 'int', 'tag_id' => 'int'],
                Schema::SCHEMA => [],
                Schema::RELATIONS => [],
            ],
        ]));
    }
}
