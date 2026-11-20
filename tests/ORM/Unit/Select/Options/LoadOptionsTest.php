<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class LoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new LoadOptions();

        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
    }

    #[\Testo\Test]
    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new LoadOptions(scope: $scope);

        \Testo\Assert::same($options->scope, $scope);
    }

    #[\Testo\Test]
    public function testScopeDisabled(): void
    {
        $options = new LoadOptions(scope: false);

        \Testo\Assert::false($options->scope);
    }

    #[\Testo\Test]
    public function testMinifyDisabled(): void
    {
        $options = new LoadOptions(minify: false);

        \Testo\Assert::false($options->minify);
    }

    #[\Testo\Test]
    public function testToArray(): void
    {
        $options = new LoadOptions(scope: false, minify: false);

        $result = $options->toArray();

        \Testo\Assert::same($result, ['scope' => false, 'minify' => false]);
    }

    #[\Testo\Test]
    public function testInheritance(): void
    {
        $options = new LoadOptions();

        \Testo\Assert::false($options instanceof \Cycle\ORM\Select\Options\JoinableLoadOptions);
    }
}
