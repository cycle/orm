<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\Options\MorphedHasOneLoadOptions;
use PHPUnit\Framework\TestCase;

final class MorphedHasOneLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new MorphedHasOneLoadOptions();

        \Testo\Assert::same($options->method, LoadMethod::SingleQuery);
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
        $options = new MorphedHasOneLoadOptions();

        \Testo\Assert::instanceOf($options, JoinableLoadOptions::class);
        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testWithWhere(): void
    {
        $where = ['{@}.status' => 'active'];
        $options = new MorphedHasOneLoadOptions(where: $where);

        \Testo\Assert::same($options->where, $where);
    }

    #[\Testo\Test]
    public function testWithOrderBy(): void
    {
        $orderBy = ['{@}.created_at' => 'DESC'];
        $options = new MorphedHasOneLoadOptions(orderBy: $orderBy);

        \Testo\Assert::same($options->orderBy, $orderBy);
    }

    #[\Testo\Test]
    public function testAllParameters(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new MorphedHasOneLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: false,
            minify: false,
            as: 'alias',
            using: 'other',
            where: $where,
            orderBy: $orderBy,
        );

        \Testo\Assert::same($options->method, LoadMethod::OuterQuery);
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
        $options = new MorphedHasOneLoadOptions();

        \Testo\Assert::equals($options->toArray(), [
            'method' => LoadMethod::SingleQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
    }

    #[\Testo\Test]
    public function testToArrayCustom(): void
    {
        $where = ['{@}.active' => true];
        $orderBy = ['{@}.name' => 'ASC'];
        $options = new MorphedHasOneLoadOptions(
            method: LoadMethod::OuterQuery,
            scope: false,
            where: $where,
            orderBy: $orderBy,
        );

        $result = $options->toArray();

        \Testo\Assert::same($result['where'], $where);
        \Testo\Assert::same($result['orderBy'], $orderBy);
        \Testo\Assert::same($result['method'], LoadMethod::OuterQuery->value);
        \Testo\Assert::false($result['scope']);
    }

    #[\Testo\Test]
    public function testToArrayWithEmptyArrays(): void
    {
        $options = new MorphedHasOneLoadOptions(where: [], orderBy: []);

        $result = $options->toArray();

        \Testo\Assert::array($result)->hasKeys('where')->hasKeys('orderBy');
        \Testo\Assert::same($result['where'], []);
        \Testo\Assert::same($result['orderBy'], []);
    }
}
