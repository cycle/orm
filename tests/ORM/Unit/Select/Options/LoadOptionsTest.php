<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class LoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new LoadOptions();

        Assert::true($options->scope);
        Assert::true($options->minify);
    }

    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new LoadOptions(scope: $scope);

        Assert::same($options->scope, $scope);
    }

    public function testScopeDisabled(): void
    {
        $options = new LoadOptions(scope: false);

        Assert::false($options->scope);
    }

    public function testMinifyDisabled(): void
    {
        $options = new LoadOptions(minify: false);

        Assert::false($options->minify);
    }

    public function testToArray(): void
    {
        $options = new LoadOptions(scope: false, minify: false);

        $result = $options->toArray();

        Assert::same($result, ['scope' => false, 'minify' => false]);
    }

    public function testInheritance(): void
    {
        $options = new LoadOptions();

        Assert::false($options instanceof \Cycle\ORM\Select\Options\JoinableLoadOptions);
    }
}
