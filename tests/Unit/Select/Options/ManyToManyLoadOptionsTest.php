<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\Options\ManyToManyLoadOptions;
use Testo\Assert;
use Testo\Test;

#[Test]
final class ManyToManyLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        Assert::same($options->method, LoadMethod::OuterQuery);
        Assert::true($options->scope);
        Assert::true($options->minify);
        Assert::null($options->as);
        Assert::null($options->using);
        Assert::null($options->where);
        Assert::null($options->orderBy);
        Assert::null($options->pivot);
    }

    public function testInheritance(): void
    {
        $options = new ManyToManyLoadOptions();

        Assert::instanceOf($options, JoinableLoadOptions::class);
        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testWithPivotOptions(): void
    {
        $pivot = ['where' => ['{@}.approved' => true]];
        $options = new ManyToManyLoadOptions(pivot: $pivot);

        Assert::same($options->pivot, $pivot);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new ManyToManyLoadOptions(where: $where);

        Assert::same($options->where, $where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new ManyToManyLoadOptions(orderBy: $orderBy);

        Assert::same($options->orderBy, $orderBy);
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

        Assert::same($options->method, LoadMethod::SingleQuery);
        Assert::false($options->scope);
        Assert::false($options->minify);
        Assert::same($options->as, 'alias');
        Assert::same($options->using, 'other');
        Assert::same($options->where, $where);
        Assert::same($options->orderBy, $orderBy);
        Assert::same($options->pivot, $pivot);
    }

    public function testToArrayDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
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

        Assert::same($result['where'], $where);
        Assert::same($result['orderBy'], $orderBy);
        Assert::same($result['pivot'], $pivot);
        Assert::same($result['method'], LoadMethod::SingleQuery->value);
        Assert::false($result['scope']);
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $options = new ManyToManyLoadOptions(where: [], orderBy: [], pivot: []);

        $result = $options->toArray();

        Assert::array($result)->hasKeys('where')->hasKeys('orderBy')->hasKeys('pivot');
        Assert::same($result['where'], []);
        Assert::same($result['orderBy'], []);
        Assert::same($result['pivot'], []);
    }
}
