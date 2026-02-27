<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\HasManyLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use PHPUnit\Framework\TestCase;

final class HasManyLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new HasManyLoadOptions();

        $this->assertSame(LoadMethod::OuterQuery, $options->method);
        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
        $this->assertNull($options->as);
        $this->assertNull($options->using);
        $this->assertNull($options->where);
        $this->assertNull($options->orderBy);
    }

    public function testInheritance(): void
    {
        $options = new HasManyLoadOptions();

        $this->assertInstanceOf(JoinableLoadOptions::class, $options);
        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'public'];
        $options = new HasManyLoadOptions(where: $where);

        $this->assertSame($where, $options->where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new HasManyLoadOptions(orderBy: $orderBy);

        $this->assertSame($orderBy, $options->orderBy);
    }

    public function testAllParameters(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.position' => 'ASC'];
        $options = new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            minify: false,
            as: 'alias',
            using: 'other',
            where: $where,
            orderBy: $orderBy,
        );

        $this->assertSame(LoadMethod::SingleQuery, $options->method);
        $this->assertFalse($options->scope);
        $this->assertFalse($options->minify);
        $this->assertSame('alias', $options->as);
        $this->assertSame('other', $options->using);
        $this->assertSame($where, $options->where);
        $this->assertSame($orderBy, $options->orderBy);
    }

    public function testToArrayDefaults(): void
    {
        $options = new HasManyLoadOptions();

        $this->assertEquals([
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }

    public function testToArrayCustom(): void
    {
        $where = ['{@}.status' => 'public'];
        $orderBy = ['{@}.position' => 'ASC'];
        $options = new HasManyLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            where: $where,
            orderBy: $orderBy,
        );

        $result = $options->toArray();

        $this->assertSame($where, $result['where']);
        $this->assertSame($orderBy, $result['orderBy']);
        $this->assertSame(LoadMethod::SingleQuery->value, $result['method']);
        $this->assertFalse($result['scope']);
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $options = new HasManyLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        $this->assertArrayHasKey('where', $result);
        $this->assertArrayHasKey('orderBy', $result);
        $this->assertSame([], $result['where']);
        $this->assertSame([], $result['orderBy']);
    }
}
