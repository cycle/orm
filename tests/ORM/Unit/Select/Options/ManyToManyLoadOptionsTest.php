<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\Options\ManyToManyLoadOptions;
use PHPUnit\Framework\TestCase;

final class ManyToManyLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        $this->assertSame(LoadMethod::OuterQuery, $options->method);
        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
        $this->assertNull($options->as);
        $this->assertNull($options->using);
        $this->assertNull($options->where);
        $this->assertNull($options->orderBy);
        $this->assertNull($options->pivot);
    }

    public function testInheritance(): void
    {
        $options = new ManyToManyLoadOptions();

        $this->assertInstanceOf(JoinableLoadOptions::class, $options);
        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testWithPivotOptions(): void
    {
        $pivot = ['where' => ['{@}.approved' => true]];
        $options = new ManyToManyLoadOptions(pivot: $pivot);

        $this->assertSame($pivot, $options->pivot);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new ManyToManyLoadOptions(where: $where);

        $this->assertSame($where, $options->where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new ManyToManyLoadOptions(orderBy: $orderBy);

        $this->assertSame($orderBy, $options->orderBy);
    }

    public function testAllParameters(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $pivot = ['where' => ['{@}.approved' => true]];
        $options = new ManyToManyLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            minify: false,
            as: 'alias',
            using: 'other',
            where: $where,
            orderBy: $orderBy,
            pivot: $pivot,
        );

        $this->assertSame(LoadMethod::SingleQuery, $options->method);
        $this->assertFalse($options->scope);
        $this->assertFalse($options->minify);
        $this->assertSame('alias', $options->as);
        $this->assertSame('other', $options->using);
        $this->assertSame($where, $options->where);
        $this->assertSame($orderBy, $options->orderBy);
        $this->assertSame($pivot, $options->pivot);
    }

    public function testToArrayDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        $this->assertSame([
            'where' => null,
            'orderBy' => null,
            'pivot' => null,
            'method' => LoadMethod::OuterQuery->value,
            'as' => null,
            'using' => null,
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }

    public function testToArrayCustom(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $pivot = ['where' => ['{@}.approved' => true]];
        $options = new ManyToManyLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            where: $where,
            orderBy: $orderBy,
            pivot: $pivot,
        );

        $result = $options->toArray();

        $this->assertSame($where, $result['where']);
        $this->assertSame($orderBy, $result['orderBy']);
        $this->assertSame($pivot, $result['pivot']);
        $this->assertSame(LoadMethod::SingleQuery->value, $result['method']);
        $this->assertFalse($result['scope']);
    }
}
