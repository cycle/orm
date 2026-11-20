<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\HasManyLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use PHPUnit\Framework\TestCase;

final class HasManyLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new HasManyLoadOptions();

        \Testo\Assert::same($options->method, LoadMethod::OuterQuery);
        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
        \Testo\Assert::null($options->as);
        \Testo\Assert::null($options->using);
        \Testo\Assert::null($options->where);
        \Testo\Assert::null($options->orderBy);
    }

    #[\Testo\Test]
    public function testInheritance(): void
    {
        $options = new HasManyLoadOptions();

        \Testo\Assert::instanceOf($options, JoinableLoadOptions::class);
        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'public'];
        $options = new HasManyLoadOptions(where: $where);

        \Testo\Assert::same($options->where, $where);
    }

    #[\Testo\Test]
    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new HasManyLoadOptions(orderBy: $orderBy);

        \Testo\Assert::same($options->orderBy, $orderBy);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($options->method, LoadMethod::SingleQuery);
        \Testo\Assert::false($options->scope);
        \Testo\Assert::false($options->minify);
        \Testo\Assert::same($options->as, 'alias');
        \Testo\Assert::same($options->using, 'other');
        \Testo\Assert::same($options->where, $where);
        \Testo\Assert::same($options->orderBy, $orderBy);
    }

    #[\Testo\Test]
    public function testToArrayDefaults(): void
    {
        $options = new HasManyLoadOptions();

        \Testo\Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($result['where'], $where);
        \Testo\Assert::same($result['orderBy'], $orderBy);
        \Testo\Assert::same($result['method'], LoadMethod::SingleQuery->value);
        \Testo\Assert::false($result['scope']);
    }

    #[\Testo\Test]
    public function testToArrayWithEmptyArrays(): void
    {
        $options = new HasManyLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        \Testo\Assert::array($result)->hasKeys('where')->hasKeys('orderBy');
        \Testo\Assert::same($result['where'], []);
        \Testo\Assert::same($result['orderBy'], []);
    }
}
