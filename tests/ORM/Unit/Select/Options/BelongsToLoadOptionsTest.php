<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use PHPUnit\Framework\TestCase;

final class BelongsToLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new BelongsToLoadOptions();

        \Testo\Assert::same($options->method, LoadMethod::OuterQuery);
        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
        \Testo\Assert::null($options->as);
        \Testo\Assert::null($options->using);
        \Testo\Assert::null($options->where);
    }

    #[\Testo\Test]
    public function testInheritance(): void
    {
        $options = new BelongsToLoadOptions();

        \Testo\Assert::instanceOf($options, JoinableLoadOptions::class);
        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new BelongsToLoadOptions(where: $where);

        \Testo\Assert::same($options->where, $where);
    }

    #[\Testo\Test]
    public function testDoesNotHaveOrderBy(): void
    {
        $options = new BelongsToLoadOptions();

        \Testo\Assert::false(property_exists($options, 'orderBy'));
    }

    #[\Testo\Test]
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

        \Testo\Assert::same($options->method, LoadMethod::SingleQuery);
        \Testo\Assert::false($options->scope);
        \Testo\Assert::false($options->minify);
        \Testo\Assert::same($options->as, 'alias');
        \Testo\Assert::same($options->using, 'other');
        \Testo\Assert::same($options->where, $where);
    }

    #[\Testo\Test]
    public function testToArrayDefaults(): void
    {
        $options = new BelongsToLoadOptions();

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
        $options = new BelongsToLoadOptions(
            method: LoadMethod::SingleQuery,
            scope: false,
            where: $where,
        );

        $result = $options->toArray();

        \Testo\Assert::same($result['where'], $where);
        \Testo\Assert::same($result['method'], LoadMethod::SingleQuery->value);
        \Testo\Assert::false($result['scope']);
    }

    #[\Testo\Test]
    public function testToArrayWithEmptyWhere(): void
    {
        $options = new BelongsToLoadOptions(where: []);

        $result = $options->toArray();

        \Testo\Assert::array($result)->hasKeys('where');
        \Testo\Assert::same($result['where'], []);
    }
}
