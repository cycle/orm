<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToMorphedLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class BelongsToMorphedLoadOptionsTest
{
    #[\Testo\Test]
    public function testDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::true($options->scope);
        \Testo\Assert::true($options->minify);
    }

    #[\Testo\Test]
    public function testExtendsLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::instanceOf($options, LoadOptions::class);
    }

    #[\Testo\Test]
    public function testDoesNotExtendJoinableLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::false($options instanceof JoinableLoadOptions);
    }

    #[\Testo\Test]
    public function testDoesNotHaveMethodProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::false(property_exists($options, 'method'));
    }

    #[\Testo\Test]
    public function testDoesNotHaveAsProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::false(property_exists($options, 'as'));
    }

    #[\Testo\Test]
    public function testDoesNotHaveUsingProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::false(property_exists($options, 'using'));
    }

    #[\Testo\Test]
    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new BelongsToMorphedLoadOptions(scope: $scope);

        \Testo\Assert::same($options->scope, $scope);
    }

    #[\Testo\Test]
    public function testScopeDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false);

        \Testo\Assert::false($options->scope);
    }

    #[\Testo\Test]
    public function testMinifyDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(minify: false);

        \Testo\Assert::false($options->minify);
    }

    #[\Testo\Test]
    public function testToArrayDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        \Testo\Assert::same($options->toArray(), [
            'scope' => true,
            'minify' => true,
        ]);
    }

    #[\Testo\Test]
    public function testToArrayCustom(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false, minify: false);

        \Testo\Assert::same($options->toArray(), [
            'scope' => false,
            'minify' => false,
        ]);
    }
}
