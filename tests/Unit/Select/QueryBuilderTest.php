<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Tests\Unit\Select\Fixture\StubLoader;
use Testo\Assert;
use Testo\Test;

/**
 * Reproduces a TypeError thrown by {@see QueryBuilder::targetFunc()} on PHP 8.3+:
 *
 *     TypeError: Cycle\ORM\Select\QueryBuilder::targetFunc():
 *     Return value must be of type callable, array returned
 *
 * The method declared `callable` as its return type while returning
 * `[$this->query, $call]` — a value that PHP's stricter return-type validation
 * no longer accepts as `callable` without further checks.
 */
#[Test]
final class QueryBuilderTest
{
    public function testTargetFuncReturnTypeAcceptsArrayCallable(): void
    {
        $builder = $this->makeBuilder();

        $method = new \ReflectionMethod($builder, 'targetFunc');

        // The return-type check is performed when the method returns,
        // so the TypeError (if any) is thrown by this invocation itself.
        $callable = $method->invoke($builder, 'where');

        Assert::callable($callable);
    }

    public function testWhereCallDoesNotThrowTypeError(): void
    {
        $builder = $this->makeBuilder();

        // End-to-end reproduction: any ->where() call on QueryBuilder
        // routes through __call() -> targetFunc(), tripping the return-type check.
        $builder->where('id', 1);

        Assert::notBlank($builder->getQuery()->getTokens()['where']);
    }

    public function testWrapWhereForwardsToWrapOnWhereInJoinedMode(): void
    {
        // A scope attached to a joined loader gets a QueryBuilder with forward='onWhere'.
        // wrapWhere() must follow the same routing as where/orWhere/andWhere — otherwise
        // the scope would wrap the parent's WHERE instead of its own JOIN's ON tokens.
        $query = new SelectQuery(['users']);
        $query
            ->leftJoin('posts')
            ->on('posts.user_id', 'users.id')
            ->onWhere('posts.published', true)
            ->orOnWhere('posts.featured', true);

        // Pretend we also have a user-level WHERE so we can verify wrapWhere
        // does NOT touch it under the forwarded routing.
        $query->where('users.active', true);

        $loader = new StubLoader();

        $builder = (new QueryBuilder($query, $loader))->withForward('onWhere');

        $builder->wrapWhere();

        $tokens = $query->getTokens();

        // JOIN's ON tokens are now a single wrapped group.
        $onTokens = $tokens['join'][1]['on'];
        Assert::same($onTokens[0], ['AND', '(']);
        Assert::same($onTokens[\count($onTokens) - 1], ['', ')']);

        // Top-level WHERE is untouched — no extra parenthesizing on the user's clause.
        $whereTokens = $tokens['where'];
        Assert::count($whereTokens, 1);
        Assert::same($whereTokens[0][0], 'AND');
        Assert::same($whereTokens[0][1][0], 'users.active');
    }

    public function testWrapWhereWithoutForwardTargetsRootWhere(): void
    {
        // Counter-case: without forwarding (root scope), wrapWhere wraps the
        // top-level WHERE tokens, not any JOIN's ON tokens.
        $query = new SelectQuery(['users']);
        $query
            ->where('users.id', 1)
            ->orWhere('users.id', 2)
            ->leftJoin('posts')->on('posts.user_id', 'users.id')->onWhere('posts.published', true);

        $loader = new StubLoader();

        $builder = new QueryBuilder($query, $loader); // no forward

        $builder->wrapWhere();

        $tokens = $query->getTokens();

        // WHERE tokens are wrapped.
        $whereTokens = $tokens['where'];
        Assert::same($whereTokens[0], ['AND', '(']);
        Assert::same($whereTokens[\count($whereTokens) - 1], ['', ')']);

        // JOIN ON tokens are NOT wrapped — they still start with the plain join key.
        $onTokens = $tokens['join'][1]['on'];
        Assert::same($onTokens[0][0], 'AND');
        // First token's payload is the [column, operator, value] triplet, not the opening paren.
        Assert::array($onTokens[0][1]);
    }

    public function getQueryReturnsACopy(): void
    {
        $builder = $this->makeBuilder();

        $builder->getQuery()->where('id', 1);

        Assert::same($builder->getQuery()->getTokens()['where'], []);
    }

    public function withForwardLeavesTheOriginalBuilderUnchanged(): void
    {
        $query = new SelectQuery(['users']);
        $query->leftJoin('posts')->on('posts.user_id', 'users.id');
        $builder = new QueryBuilder($query, new StubLoader());

        $builder->withForward('onWhere');
        $builder->where('users.active', true);

        Assert::count($query->getTokens()['where'], 1);
        Assert::count($query->getTokens()['join'][1]['on'], 1);
    }

    public function withQueryLeavesTheOriginalBuilderUnchanged(): void
    {
        $query = new SelectQuery(['users']);
        $builder = new QueryBuilder($query, new StubLoader());

        $builder->withQuery(new SelectQuery(['posts']))->where('id', 2);
        $builder->where('id', 1);

        Assert::count($query->getTokens()['where'], 1);
        Assert::same($query->getTokens()['where'][0][1][0], 'users.id');
    }

    private function makeBuilder(): QueryBuilder
    {
        $query = new SelectQuery(['users']);

        $loader = new StubLoader();

        return new QueryBuilder($query, $loader);
    }
}
