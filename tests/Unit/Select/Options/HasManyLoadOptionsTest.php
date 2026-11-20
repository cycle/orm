<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\HasManyLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Testo\Assert;
use Testo\Test;

#[Test]
final class HasManyLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new HasManyLoadOptions();

        Assert::same($options->method, LoadMethod::OuterQuery);
        Assert::true($options->scope);
        Assert::true($options->minify);
        Assert::null($options->as);
        Assert::null($options->using);
        Assert::null($options->where);
        Assert::null($options->orderBy);
    }

    public function testInheritance(): void
    {
        $options = new HasManyLoadOptions();

        Assert::instanceOf($options, JoinableLoadOptions::class);
        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'public'];
        $options = new HasManyLoadOptions(where: $where);

        Assert::same($options->where, $where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new HasManyLoadOptions(orderBy: $orderBy);

        Assert::same($options->orderBy, $orderBy);
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

        Assert::same($options->method, LoadMethod::SingleQuery);
        Assert::false($options->scope);
        Assert::false($options->minify);
        Assert::same($options->as, 'alias');
        Assert::same($options->using, 'other');
        Assert::same($options->where, $where);
        Assert::same($options->orderBy, $orderBy);
    }

    public function testToArrayDefaults(): void
    {
        $options = new HasManyLoadOptions();

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
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

        Assert::same($result['where'], $where);
        Assert::same($result['orderBy'], $orderBy);
        Assert::same($result['method'], LoadMethod::SingleQuery->value);
        Assert::false($result['scope']);
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $options = new HasManyLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        Assert::array($result)->hasKeys('where')->hasKeys('orderBy');
        Assert::same($result['where'], []);
        Assert::same($result['orderBy'], []);
    }
}
