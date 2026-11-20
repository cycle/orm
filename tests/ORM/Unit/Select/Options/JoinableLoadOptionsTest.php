<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\JoinMethod;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class JoinableLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new JoinableLoadOptions();

        \Testo\Assert::null($options->method);
        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
        \Testo\Assert::null($options->as);
        \Testo\Assert::null($options->using);
    }

    #[\Testo\Test]
    public function testExtendsLoadOptions(): void
    {
        $options = new JoinableLoadOptions();

        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testWithLoadMethod(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::SingleQuery);

        \Testo\Assert::same($options->method, LoadMethod::SingleQuery);
    }

    #[\Testo\Test]
    public function testWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::InnerJoin);

        \Testo\Assert::same($options->method, JoinMethod::InnerJoin);
    }

    #[\Testo\Test]
    public function testWithLeftJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        \Testo\Assert::same($options->method, JoinMethod::LeftJoin);
    }

    #[\Testo\Test]
    public function testCustomAlias(): void
    {
        $options = new JoinableLoadOptions(as: 'my_alias');

        \Testo\Assert::same($options->as, 'my_alias');
    }

    #[\Testo\Test]
    public function testUsing(): void
    {
        $options = new JoinableLoadOptions(using: 'other_relation');

        \Testo\Assert::same($options->using, 'other_relation');
    }

    #[\Testo\Test]
    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new JoinableLoadOptions(scope: $scope);

        \Testo\Assert::same($options->scope, $scope);
    }

    #[\Testo\Test]
    public function testAllParameters(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new JoinableLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: $scope,
            minify: false,
            as: 'alias',
            using: 'other',
        );

        \Testo\Assert::same($options->method, LoadMethod::OuterQuery);
        \Testo\Assert::same($options->scope, $scope);
        \Testo\Assert::false($options->minify);
        \Testo\Assert::same($options->as, 'alias');
        \Testo\Assert::same($options->using, 'other');
    }

    #[\Testo\Test]
    public function testToArrayWithLoadMethod(): void
    {
        $options = new JoinableLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            minify: false,
            as: 'my_alias',
            using: 'other',
        );

        \Testo\Assert::equals($options->toArray(), [
            'method' => LoadMethod::SingleQuery->value,
            'as' => 'my_alias',
            'using' => 'other',
            'scope' => false,
            'minify' => false,
        ]);
    }

    #[\Testo\Test]
    public function testToArrayWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        \Testo\Assert::same($options->toArray()['method'], JoinMethod::LeftJoin->value);
    }

    #[\Testo\Test]
    public function testToArrayDefaults(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::OuterQuery);

        \Testo\Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
    }
}
