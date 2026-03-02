<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\HasOneLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\JoinMethod;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use PHPUnit\Framework\TestCase;

final class HasOneLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new HasOneLoadOptions();

        $this->assertSame(LoadMethod::SingleQuery, $options->method);
        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
        $this->assertNull($options->as);
        $this->assertNull($options->using);
        $this->assertNull($options->where);
        $this->assertNull($options->orderBy);
    }

    public function testInheritance(): void
    {
        $options = new HasOneLoadOptions();

        $this->assertInstanceOf(JoinableLoadOptions::class, $options);
        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'active'];
        $options = new HasOneLoadOptions(where: $where);

        $this->assertSame($where, $options->where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new HasOneLoadOptions(orderBy: $orderBy);

        $this->assertSame($orderBy, $options->orderBy);
    }

    public function testWithJoinMethod(): void
    {
        $options = new HasOneLoadOptions(method: JoinMethod::LeftJoin);

        $this->assertSame(JoinMethod::LeftJoin, $options->method);
    }

    public function testAllParameters(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new HasOneLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: false,
            minify: false,
            as: 'alias',
            using: 'other',
            where: $where,
            orderBy: $orderBy,
        );

        $this->assertSame(LoadMethod::OuterQuery, $options->method);
        $this->assertFalse($options->scope);
        $this->assertFalse($options->minify);
        $this->assertSame('alias', $options->as);
        $this->assertSame('other', $options->using);
        $this->assertSame($where, $options->where);
        $this->assertSame($orderBy, $options->orderBy);
    }

    public function testToArrayDefaults(): void
    {
        $options = new HasOneLoadOptions();

        $this->assertEquals([
            'method' => LoadMethod::SingleQuery->value,
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }

    public function testToArrayCustom(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new HasOneLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: false,
            as: 'alias',
            where: $where,
            orderBy: $orderBy,
        );

        $result = $options->toArray();

        $this->assertSame($where, $result['where']);
        $this->assertSame($orderBy, $result['orderBy']);
        $this->assertSame(LoadMethod::OuterQuery->value, $result['method']);
        $this->assertSame('alias', $result['as']);
        $this->assertFalse($result['scope']);
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $options = new HasOneLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('orderBy', $result);
        $this->assertSame([], $result['where']);
        $this->assertSame([], $result['orderBy']);
    }
}
