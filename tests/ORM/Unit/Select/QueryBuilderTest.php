<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\QueryBuilder;
use PHPUnit\Framework\TestCase;

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
final class QueryBuilderTest extends TestCase
{
    public function testTargetFuncReturnTypeAcceptsArrayCallable(): void
    {
        $builder = $this->makeBuilder();

        $method = new \ReflectionMethod($builder, 'targetFunc');

        // The return-type check is performed when the method returns,
        // so the TypeError (if any) is thrown by this invocation itself.
        $callable = $method->invoke($builder, 'where');

        $this->assertIsCallable($callable);
    }

    public function testWhereCallDoesNotThrowTypeError(): void
    {
        $builder = $this->makeBuilder();

        // End-to-end reproduction: any ->where() call on QueryBuilder
        // routes through __call() -> targetFunc(), tripping the return-type check.
        $builder->where('id', 1);

        $this->assertNotEmpty($builder->getQuery()->getTokens()['where']);
    }

    private function makeBuilder(): QueryBuilder
    {
        $query = new SelectQuery(['users']);

        $loader = $this->createMock(AbstractLoader::class);
        $loader->method('fieldAlias')->willReturn(null);
        $loader->method('getAlias')->willReturn('users');
        $loader->method('getTarget')->willReturn('user');
        $loader->method('getParentLoader')->willReturn(null);

        return new QueryBuilder($query, $loader);
    }
}
