<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Tests\Unit\Select\Fixture\StubLoader;

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
final class QueryBuilderTest
{
    #[\Testo\Test]
    public function testTargetFuncReturnTypeAcceptsArrayCallable(): void
    {
        $builder = $this->makeBuilder();

        $method = new \ReflectionMethod($builder, 'targetFunc');

        // The return-type check is performed when the method returns,
        // so the TypeError (if any) is thrown by this invocation itself.
        $callable = $method->invoke($builder, 'where');

        \Testo\Assert::callable($callable);
    }

    #[\Testo\Test]
    public function testWhereCallDoesNotThrowTypeError(): void
    {
        $builder = $this->makeBuilder();

        // End-to-end reproduction: any ->where() call on QueryBuilder
        // routes through __call() -> targetFunc(), tripping the return-type check.
        $builder->where('id', 1);

        \Testo\Assert::notBlank($builder->getQuery()->getTokens()['where']);
    }

    #[\Testo\Test]
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
        \Testo\Assert::same($onTokens[0], ['AND', '(']);
        \Testo\Assert::same($onTokens[\count($onTokens) - 1], ['', ')']);

        // Top-level WHERE is untouched — no extra parenthesizing on the user's clause.
        $whereTokens = $tokens['where'];
        \Testo\Assert::count($whereTokens, 1);
        \Testo\Assert::same($whereTokens[0][0], 'AND');
        \Testo\Assert::same($whereTokens[0][1][0], 'users.active');
    }

    #[\Testo\Test]
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
        \Testo\Assert::same($whereTokens[0], ['AND', '(']);
        \Testo\Assert::same($whereTokens[\count($whereTokens) - 1], ['', ')']);

        // JOIN ON tokens are NOT wrapped — they still start with the plain join key.
        $onTokens = $tokens['join'][1]['on'];
        \Testo\Assert::same($onTokens[0][0], 'AND');
        // First token's payload is the [column, operator, value] triplet, not the opening paren.
        \Testo\Assert::array($onTokens[0][1]);
    }

    private function makeBuilder(): QueryBuilder
    {
        $query = new SelectQuery(['users']);

        $loader = new StubLoader();

        return new QueryBuilder($query, $loader);
    }

}
