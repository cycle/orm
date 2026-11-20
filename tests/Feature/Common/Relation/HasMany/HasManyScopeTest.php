<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\HasMany;

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
use Testo\Expect;
use Testo\Lifecycle\BeforeTest;
use Testo\Test;

final class HasManyScopeTest extends OrmTestCase
{
    #[Test]
    public function testScopeOrdered(): void
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
    public function testScopeOrderedAsc(): void
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
    public function testScopeOrderedAscInLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
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
    public function testScopeOrderedPromisedAsc(): void
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
    public function testScopeOrderedAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
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
    public function testScopeOrderedAndWherePromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
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
    public function testScopeOrderedAndWhereReversed(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
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
    public function testScopeOrderedAndWhereReversedInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
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
    public function testScopeOrderedAndWhereReversedPromised(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'DESC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
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
    public function testScopeOrderedAndCustomWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
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
    public function testScopeOrderedAndCustomWhereInload(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope([], ['@.level' => 'ASC']),
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => ['>=' => 2]]],
        ]);

        // overwrites default one
        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->load('comments', [
            'where' => ['@.level' => 1],
            'method' => JoinableLoader::INLOAD,
        ])->fetchAll();

        Assert::count($a->comments, 1);
        Assert::count($b->comments, 1);

        Assert::same($a->comments[0]->message, 'msg 1');
        Assert::same($b->comments[0]->message, 'msg 2.1');
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
    public function testWithOrderByInLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
        ])->orderBy('user.id')->fetchAll();

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
    public function testWithOrderByLazyLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->orderBy('user.id')->fetchAll();

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
    public function testWithOrderByAltered(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::ORDER_BY => ['@.level' => 'DESC'],
            ],
        ]);

        [$a, $b] = (new Select($this->orm, User::class))->load('comments', [
            'orderBy' => ['@.level' => 'ASC'],
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
    public function testWithOrderByAndWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [
                Relation::WHERE => ['@.level' => ['>=' => 2]],
                Relation::ORDER_BY => ['@.level' => 'ASC'],
            ],
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
    public function testWithWhere(): void
    {
        $this->orm = $this->withCommentsSchema([
            Relation::SCHEMA => [Relation::WHERE => ['@.level' => 4]],
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
            Relation::SCHEMA => ['@.level' => 4],
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
            ->load('comments', ['method' => JoinableLoader::INLOAD])
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
        $res = (new Select($this->orm, User::class))->load('comments', [
            'method' => JoinableLoader::INLOAD,
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

    /**
     * Documents the historical bug: when a joined-loader scope adds a plain top-level
     * condition (no wrapWhere), an adversarial `orWhere` injected via the 'load' option
     * bypasses the scope due to AND-over-OR precedence inside the JOIN's ON clause.
     */
    #[Test]
    public function testInloadJoinedScopeWithoutWrapWhereIsBypassedByAdversarialOrLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            // Plain QueryScope — adds `level >= 2` as a flat AND token, no wrap.
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 2]]),
        ]);

        $res = (new Select($this->orm, User::class))
            ->load('comments', [
                'method' => JoinableLoader::INLOAD,
                'load' => static function (Select\QueryBuilder $q): void {
                    // top-level ON-condition WHERE + OR — the scope's AND lands at the
                    // tail of the same flat token list:
                    //   ON join_key AND level = 1 OR message = 'msg 4' AND level >= 2
                    //   ≡ (join_key AND level = 1) OR (message = 'msg 4' AND level >= 2)
                    // The first OR-arm ignores `level >= 2` — scope bypassed.
                    $q->where('@.level', 1)
                        ->orWhere('@.message', 'msg 4');
                },
            ])
            ->orderBy('user.id')
            ->fetchAll();

        [$userA, $userB] = $res;

        // User A: msg 1 (level=1) sneaks through the first OR-arm; msg 4 matches the
        // second arm legitimately.
        Assert::count($userA->comments, 2);
        $aMessages = $this->extractMessages($userA->comments);
        Assert::same($aMessages, ['msg 1', 'msg 4']);

        // User B: msg 2.1 (level=1) sneaks through the first arm — scope leaks again.
        Assert::count($userB->comments, 1);
        Assert::same($userB->comments[0]->message, 'msg 2.1');
    }

    /**
     * Companion to the test above: with `QueryScope(..., wrapWhere: true)`,
     * QueryBuilder::targetFunc() forwards the wrap to `wrapOnWhere` on the JOIN's ON tokens,
     * enclosing the user's OR group. The scope's `level >= 2` condition stays effective.
     */
    #[Test]
    public function testInloadJoinedScopeWithWrapWhereProtectsAgainstAdversarialOrLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 2]], wrapWhere: true),
        ]);

        $res = (new Select($this->orm, User::class))
            ->load('comments', [
                'method' => JoinableLoader::INLOAD,
                'load' => static function (Select\QueryBuilder $q): void {
                    $q->where('@.level', 1)
                        ->orWhere('@.message', 'msg 4');
                },
            ])
            ->orderBy('user.id')
            ->fetchAll();

        [$userA, $userB] = $res;

        // ON ((join_key AND level = 1) OR message = 'msg 4') AND level >= 2
        // User A: only msg 4 (level = 4) matches the OR group AND scope.
        Assert::count($userA->comments, 1);
        Assert::same($userA->comments[0]->message, 'msg 4');

        // User B: no comment satisfies both the OR group and `level >= 2`.
        Assert::count($userB->comments, 0);
    }

    /**
     * Sanity check: a wrap-aware scope works correctly in plain INLOAD joining without
     * any adversarial conditions — wrapWhere() on empty ON tokens is a no-op and the
     * scope adds its condition at the top level of the JOIN's ON clause.
     */
    #[Test]
    public function testInloadJoinedScopeWithWrapWhereWithoutAdversarialLoad(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 3]], wrapWhere: true),
        ]);

        $res = (new Select($this->orm, User::class))
            ->load('comments', [
                'method' => JoinableLoader::INLOAD,
            ])
            ->orderBy('user.id')
            ->fetchAll();

        [$userA, $userB] = $res;

        // Only level >= 3 comments survive: msg 3, msg 4, msg 2.3
        Assert::count($userA->comments, 2);
        $aMessages = $this->extractMessages($userA->comments);
        Assert::same($aMessages, ['msg 3', 'msg 4']);

        Assert::count($userB->comments, 1);
        Assert::same($userB->comments[0]->message, 'msg 2.3');
    }

    /**
     * Regression: an override scope passed to load() (here `scope: false`, disabling the
     * relation's source scope) must survive a clone of the Select. fetchAll() does not clone
     * for a POSTLOAD relation, so it already worked; cloning re-parents the loader tree via
     * AbstractLoader::__clone() → JoinableLoader::withContext() with empty options, whose
     * `else` branch used to blindly re-apply the source scope, dropping the override.
     */
    #[Test]
    public function testDisabledRelationScopeSurvivesClone(): void
    {
        $this->orm = $this->withCommentsSchema([
            // Source scope on Comment: only level >= 2 (drops 'msg 1' and 'msg 2.1').
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 2]]),
        ]);

        $select = (new Select($this->orm, User::class))
            ->load('comments', ['scope' => false])
            ->orderBy('user.id');

        // Clone before executing anything so neither result is served from the heap.
        $clone = clone $select;

        // Baseline (no clone): override works, all comments are loaded.
        [$a, $b] = $select->fetchAll();
        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);

        // Drop identity map so the cloned query re-reads comments from the database
        // instead of returning the already-hydrated User instances above.
        $this->orm->getHeap()->clean();

        // The clone must preserve the disabled scope.
        [$a, $b] = $clone->fetchAll();
        Assert::count($a->comments, 4);
        Assert::count($b->comments, 3);
    }

    /**
     * Regression: fetchOne() internally does `(clone $this)->where(...)->limit(1)`, so the
     * `scope: false` override must reach the cloned loader. Before the fix the source scope
     * (level >= 2) leaked back in and 'msg 1' was filtered out.
     */
    #[Test]
    public function testFetchOneKeepsDisabledRelationScope(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 2]]),
        ]);

        $user = (new Select($this->orm, User::class))
            ->load('comments', ['scope' => false])
            ->wherePK(1)
            ->fetchOne();

        Assert::notNull($user);
        Assert::count($user->comments, 4);
    }

    /**
     * Regression: a custom override scope (not just `false`) must also survive the fetchOne()
     * clone. Here load() overrides the source scope (level >= 2) with a stricter one
     * (level >= 3). Before the fix the clone reset it back to the source scope, yielding 3
     * comments instead of 2.
     */
    #[Test]
    public function testFetchOneKeepsCustomRelationScopeOverride(): void
    {
        $this->orm = $this->withCommentsSchema([
            Schema::SCOPE => new Select\QueryScope(['@.level' => ['>=' => 2]]),
        ]);

        $user = (new Select($this->orm, User::class))
            ->load('comments', ['scope' => new Select\QueryScope(['@.level' => ['>=' => 3]])])
            ->wherePK(1)
            ->fetchOne();

        Assert::notNull($user);
        // level 3 and 4 only.
        Assert::count($user->comments, 2);
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
        (new Select($this->orm, User::class))->load('comments', [
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

    /**
     * @return list<string>
     */
    private function extractMessages(iterable $comments): array
    {
        $messages = [];
        foreach ($comments as $comment) {
            $messages[] = $comment->message;
        }
        \sort($messages);

        return $messages;
    }
}
