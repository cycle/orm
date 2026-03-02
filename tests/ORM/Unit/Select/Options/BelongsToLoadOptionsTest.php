<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use PHPUnit\Framework\TestCase;

final class BelongsToLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new BelongsToLoadOptions();

        $this->assertSame(LoadMethod::OuterQuery, $options->method);
        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
        $this->assertNull($options->as);
        $this->assertNull($options->using);
        $this->assertNull($options->where);
    }

    public function testInheritance(): void
    {
        $options = new BelongsToLoadOptions();

        $this->assertInstanceOf(JoinableLoadOptions::class, $options);
        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new BelongsToLoadOptions(where: $where);

        $this->assertSame($where, $options->where);
    }

    public function testDoesNotHaveOrderBy(): void
    {
        $options = new BelongsToLoadOptions();

        $this->assertFalse(property_exists($options, 'orderBy'));
    }

    public function testAllParameters(): void
    {
        $where = ['{@}.status' => 'published'];
        $options = new BelongsToLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            minify: false,
            as: 'alias',
            using: 'other',
            where: $where,
        );

        $this->assertSame(LoadMethod::SingleQuery, $options->method);
        $this->assertFalse($options->scope);
        $this->assertFalse($options->minify);
        $this->assertSame('alias', $options->as);
        $this->assertSame('other', $options->using);
        $this->assertSame($where, $options->where);
    }

    public function testToArrayDefaults(): void
    {
        $options = new BelongsToLoadOptions();

        $this->assertEquals([
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }

    public function testToArrayCustom(): void
    {
        $where = ['{@}.active' => true];
        $options = new BelongsToLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            where: $where,
        );

        $result = $options->toArray();

        $this->assertSame($where, $result['where']);
        $this->assertSame(LoadMethod::SingleQuery->value, $result['method']);
        $this->assertFalse($result['scope']);
    }

    public function testToArrayWithEmptyWhere(): void
    {
        $options = new BelongsToLoadOptions(where: []);

        $result = $options->toArray();

        $this->assertArrayHasKey('where', $result);
        $this->assertSame([], $result['where']);
    }
}
