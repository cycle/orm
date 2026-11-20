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
use Cycle\ORM\Tests\Feature\Common\BaseTest;
use Cycle\ORM\Tests\Traits\TableTrait;

abstract class ManyToManyScopeTest extends BaseTest
{
    use TableTrait;

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
        \Testo\Assert::same($a->tags[1]->name, 'tag b');
        \Testo\Assert::same($a->tags[2]->name, 'tag d');
        \Testo\Assert::same($a->tags[3]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 4);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[3]->name, 'tag a');
        \Testo\Assert::same($a->tags[2]->name, 'tag b');
        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag d');
        \Testo\Assert::same($a->tags[1]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag d');
        \Testo\Assert::same($a->tags[1]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[0]->name, 'tag d');
        \Testo\Assert::same($a->tags[1]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);

        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[0]->name, 'tag d');
        \Testo\Assert::same($a->tags[1]->name, 'tag e');

        \Testo\Assert::same($b->tags[0]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[2]->name, 'tag f');
    }

    #[\Testo\Test]
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

        $this->captureReadQueries();
        \Testo\Assert::count($a->tags, 2);
        \Testo\Assert::count($b->tags, 3);
        $this->assertNumReads(2);

        \Testo\Assert::same($a->tags[1]->name, 'tag d');
        \Testo\Assert::same($a->tags[0]->name, 'tag e');

        \Testo\Assert::same($b->tags[2]->name, 'tag c');
        \Testo\Assert::same($b->tags[1]->name, 'tag d');
        \Testo\Assert::same($b->tags[0]->name, 'tag f');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 1);
        \Testo\Assert::count($b->tags, 0);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
    }

    #[\Testo\Test]
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

        \Testo\Assert::count($a->tags, 1);

        \Testo\Assert::same($a->tags[0]->name, 'tag a');
    }

    #[\Testo\Test]
    public function testWithWhere(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 6]]],
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($res, 1);
        \Testo\Assert::same($res[0]->email, 'another@world.com');
    }

    #[\Testo\Test]
    public function testWithWhereAltered(): void
    {
        $this->orm = $this->withTagSchema([
            Relation::WHERE => ['@.level' => ['>=' => 6]],
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags', [
            'where' => ['@.level' => ['>=' => 5]],
        ])->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($res, 2);
        \Testo\Assert::same($res[0]->email, 'hello@world.com');
        \Testo\Assert::same($res[1]->email, 'another@world.com');
    }

    #[\Testo\Test]
    public function testLimitParentSelection(): void
    {
        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags')
            ->limit(1)
            ->orderBy('user.id')->fetchAll();

        \Testo\Assert::count($res, 1);
        \Testo\Assert::same($res[0]->email, 'hello@world.com');
        \Testo\Assert::count($res[0]->tags, 4);
    }

    #[\Testo\Test]
    public function testLimitParentSelectionError(): void
    {
        \Testo\Expect::exception(LoaderException::class);

        $this->orm = $this->withTagSchema([]);

        $selector = new Select($this->orm, User::class);

        // second user has been filtered out
        $res = $selector
            ->load('tags', ['method' => Select\JoinableLoader::INLOAD])
            ->limit(1)
            ->orderBy('user.id')->fetchAll();
    }

    #[\Testo\Test]
    public function testInvalidOrderBy(): void
    {
        \Testo\Expect::exception(StatementException::class);

        $this->orm = $this->withTagSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.column' => 'ASC']),
        ]);

        $selector = new Select($this->orm, User::class);

        $res = $selector->with('tags')->orderBy('user.id')->fetchAll();
    }

    #[\Testo\Lifecycle\BeforeTest]
    public function setUp(): void
    {
        parent::setUp();

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
