<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\Options\ManyToManyLoadOptions;
use PHPUnit\Framework\TestCase;

final class ManyToManyLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        \Testo\Assert::same($options->method, LoadMethod::OuterQuery);
        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
        \Testo\Assert::null($options->as);
        \Testo\Assert::null($options->using);
        \Testo\Assert::null($options->where);
        \Testo\Assert::null($options->orderBy);
        \Testo\Assert::null($options->pivot);
    }

    #[\Testo\Test]
    public function testInheritance(): void
    {
        $options = new ManyToManyLoadOptions();

        \Testo\Assert::instanceOf($options, JoinableLoadOptions::class);
        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testWithPivotOptions(): void
    {
        $pivot = ['where' => ['{@}.approved' => true]];
        $options = new ManyToManyLoadOptions(pivot: $pivot);

        \Testo\Assert::same($options->pivot, $pivot);
    }

    #[\Testo\Test]
    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new ManyToManyLoadOptions(where: $where);

        \Testo\Assert::same($options->where, $where);
    }

    #[\Testo\Test]
    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new ManyToManyLoadOptions(orderBy: $orderBy);

        \Testo\Assert::same($options->orderBy, $orderBy);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($options->method, LoadMethod::SingleQuery);
        \Testo\Assert::false($options->scope);
        \Testo\Assert::false($options->minify);
        \Testo\Assert::same($options->as, 'alias');
        \Testo\Assert::same($options->using, 'other');
        \Testo\Assert::same($options->where, $where);
        \Testo\Assert::same($options->orderBy, $orderBy);
        \Testo\Assert::same($options->pivot, $pivot);
    }

    #[\Testo\Test]
    public function testToArrayDefaults(): void
    {
        $options = new ManyToManyLoadOptions();

        \Testo\Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($result['where'], $where);
        \Testo\Assert::same($result['orderBy'], $orderBy);
        \Testo\Assert::same($result['pivot'], $pivot);
        \Testo\Assert::same($result['method'], LoadMethod::SingleQuery->value);
        \Testo\Assert::false($result['scope']);
    }

    #[\Testo\Test]
    public function testToArrayWithEmptyArrays(): void
    {
        $options = new ManyToManyLoadOptions(where: [], orderBy: [], pivot: []);

        $result = $options->toArray();

        \Testo\Assert::array($result)->hasKeys('where')->hasKeys('orderBy')->hasKeys('pivot');
        \Testo\Assert::same($result['where'], []);
        \Testo\Assert::same($result['orderBy'], []);
        \Testo\Assert::same($result['pivot'], []);
    }
}
