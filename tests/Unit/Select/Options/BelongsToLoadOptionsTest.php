<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadMethod;
use Cycle\ORM\Select\Options\LoadOptions;
use Testo\Assert;
use Testo\Test;

#[Test]
final class BelongsToLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new BelongsToLoadOptions();

        Assert::same($options->method, LoadMethod::OuterQuery);
        Assert::true($options->scope);
        Assert::true($options->minify);
        Assert::null($options->as);
        Assert::null($options->using);
        Assert::null($options->where);
    }

    public function testInheritance(): void
    {
        $options = new BelongsToLoadOptions();

        Assert::instanceOf($options, JoinableLoadOptions::class);
        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testWithWhere(): void
    {
        $where = ['{@}.active' => true];
        $options = new BelongsToLoadOptions(where: $where);

        Assert::same($options->where, $where);
    }

    public function testDoesNotHaveOrderBy(): void
    {
        $options = new BelongsToLoadOptions();

        Assert::false(property_exists($options, 'orderBy'));
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

        Assert::same($options->method, LoadMethod::SingleQuery);
        Assert::false($options->scope);
        Assert::false($options->minify);
        Assert::same($options->as, 'alias');
        Assert::same($options->using, 'other');
        Assert::same($options->where, $where);
    }

    public function testToArrayDefaults(): void
    {
        $options = new BelongsToLoadOptions();

        Assert::equals($options->toArray(), [
            'method' => LoadMethod::OuterQuery->value,
            'scope' => true,
            'minify' => true,
        ]);
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

        Assert::same($result['where'], $where);
        Assert::same($result['method'], LoadMethod::SingleQuery->value);
        Assert::false($result['scope']);
    }

    public function testToArrayWithEmptyWhere(): void
    {
        $options = new BelongsToLoadOptions(where: []);

        $result = $options->toArray();

        Assert::array($result)->hasKeys('where');
        Assert::same($result['where'], []);
    }
}
