<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\HasOneLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\JoinMethod;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Testo\Assert;
use Testo\Test;

#[Test]
final class HasOneLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new HasOneLoadOptions();

        Assert::same($options->method, LoadMethod::SingleQuery);
        Assert::true($options->scope);
        Assert::true($options->minify);
        Assert::null($options->as);
        Assert::null($options->using);
        Assert::null($options->where);
        Assert::null($options->orderBy);
    }

    public function testInheritance(): void
    {
        $options = new HasOneLoadOptions();

        Assert::instanceOf($options, JoinableLoadOptions::class);
        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'active'];
        $options = new HasOneLoadOptions(where: $where);

        Assert::same($options->where, $where);
    }

    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new HasOneLoadOptions(orderBy: $orderBy);

        Assert::same($options->orderBy, $orderBy);
    }

    public function testWithJoinMethod(): void
    {
        $options = new HasOneLoadOptions(method: JoinMethod::LeftJoin);

        Assert::same($options->method, JoinMethod::LeftJoin);
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

        Assert::same($options->method, LoadMethod::OuterQuery);
        Assert::false($options->scope);
        Assert::false($options->minify);
        Assert::same($options->as, 'alias');
        Assert::same($options->using, 'other');
        Assert::same($options->where, $where);
        Assert::same($options->orderBy, $orderBy);
    }

    public function testToArrayDefaults(): void
    {
        $options = new HasOneLoadOptions();

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::SingleQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
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

        Assert::same($result['where'], $where);
        Assert::same($result['orderBy'], $orderBy);
        Assert::same($result['method'], LoadMethod::OuterQuery->value);
        Assert::same($result['as'], 'alias');
        Assert::false($result['scope']);
    }

    public function testToArrayWithEmptyArrays(): void
    {
        $options = new HasOneLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        Assert::array($result)->hasKeys('where')->hasKeys('orderBy');
        Assert::same($result['where'], []);
        Assert::same($result['orderBy'], []);
    }
}
