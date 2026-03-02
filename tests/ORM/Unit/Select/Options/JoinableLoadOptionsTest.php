<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\JoinMethod;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class JoinableLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new JoinableLoadOptions();

        $this->assertNull($options->method);
        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
        $this->assertNull($options->as);
        $this->assertNull($options->using);
    }

    public function testExtendsLoadOptions(): void
    {
        $options = new JoinableLoadOptions();

        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testWithLoadMethod(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::SingleQuery);

        $this->assertSame(LoadMethod::SingleQuery, $options->method);
    }

    public function testWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::InnerJoin);

        $this->assertSame(JoinMethod::InnerJoin, $options->method);
    }

    public function testWithLeftJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        $this->assertSame(JoinMethod::LeftJoin, $options->method);
    }

    public function testCustomAlias(): void
    {
        $options = new JoinableLoadOptions(as: 'my_alias');

        $this->assertSame('my_alias', $options->as);
    }

    public function testUsing(): void
    {
        $options = new JoinableLoadOptions(using: 'other_relation');

        $this->assertSame('other_relation', $options->using);
    }

    public function testCustomScope(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $options = new JoinableLoadOptions(scope: $scope);

        $this->assertSame($scope, $options->scope);
    }

    public function testAllParameters(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $options = new JoinableLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: $scope,
            minify: false,
            as: 'alias',
            using: 'other',
        );

        $this->assertSame(LoadMethod::OuterQuery, $options->method);
        $this->assertSame($scope, $options->scope);
        $this->assertFalse($options->minify);
        $this->assertSame('alias', $options->as);
        $this->assertSame('other', $options->using);
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

        $this->assertEquals([
            'method' => LoadMethod::SingleQuery->value,
            'as' => 'my_alias',
            'using' => 'other',
            'scope' => false,
            'minify' => false,
        ], $options->toArray());
    }

    public function testToArrayWithJoinMethod(): void
    {
        $options = new JoinableLoadOptions(method: JoinMethod::LeftJoin);

        $this->assertSame(JoinMethod::LeftJoin->value, $options->toArray()['method']);
    }

    public function testToArrayDefaults(): void
    {
        $options = new JoinableLoadOptions(method: LoadMethod::OuterQuery);

        $this->assertEquals([
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }
}
