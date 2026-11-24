<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\JoinMethod;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class JoinableLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new JoinableLoadOptions();

        Assert::null($options->method);
        Assert::true($options->scope);
        Assert::true($options->minify);
        Assert::null($options->as);
        Assert::null($options->using);
    }

    public function testExtendsLoadOptions(): void
    {
        $options = new JoinableLoadOptions();

        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testWithLoadMethod(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::SingleQuery);

        Assert::same($options->method, LoadMethod::SingleQuery);
    }

    public function testWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::InnerJoin);

        Assert::same($options->method, JoinMethod::InnerJoin);
    }

    public function testWithLeftJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        Assert::same($options->method, JoinMethod::LeftJoin);
    }

    public function testCustomAlias(): void
    {
        $options = new JoinableLoadOptions(as: 'my_alias');

        Assert::same($options->as, 'my_alias');
    }

    public function testUsing(): void
    {
        $options = new JoinableLoadOptions(using: 'other_relation');

        Assert::same($options->using, 'other_relation');
    }

    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new JoinableLoadOptions(scope: $scope);

        Assert::same($options->scope, $scope);
    }

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

        Assert::same($options->method, LoadMethod::OuterQuery);
        Assert::same($options->scope, $scope);
        Assert::false($options->minify);
        Assert::same($options->as, 'alias');
        Assert::same($options->using, 'other');
    }

    public function testToArrayWithLoadMethod(): void
    {
        $options = new JoinableLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            minify: false,
            as: 'my_alias',
            using: 'other',
        );

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::SingleQuery->value,
            'as' => 'my_alias',
            'using' => 'other',
            'scope' => false,
            'minify' => false,
        ]);
    }

    public function testToArrayWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        Assert::same($options->toArray()['method'], JoinMethod::LeftJoin->value);
    }

    public function testToArrayDefaults(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::OuterQuery);

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
    }
}
