<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToMorphedLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use Testo\Assert;
use Testo\Test;

#[Test]
final class BelongsToMorphedLoadOptionsTest
{
    public function testDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::true($options->scope);
        Assert::true($options->minify);
    }

    public function testExtendsLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::instanceOf($options, LoadOptions::class);
    }

    public function testDoesNotExtendJoinableLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::false($options instanceof JoinableLoadOptions);
    }

    public function testDoesNotHaveMethodProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::false(property_exists($options, 'method'));
    }

    public function testDoesNotHaveAsProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::false(property_exists($options, 'as'));
    }

    public function testDoesNotHaveUsingProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::false(property_exists($options, 'using'));
    }

    public function testCustomScope(): void
    {
        $scope = \Mockery::mock(ScopeInterface::class)->shouldIgnoreMissing();
        $options = new BelongsToMorphedLoadOptions(scope: $scope);

        Assert::same($options->scope, $scope);
    }

    public function testScopeDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false);

        Assert::false($options->scope);
    }

    public function testMinifyDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(minify: false);

        Assert::false($options->minify);
    }

    public function testToArrayDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        Assert::same($options->toArray(), [
            'scope' => true,
            'minify' => true,
        ]);
    }

    public function testToArrayCustom(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false, minify: false);

        Assert::same($options->toArray(), [
            'scope' => false,
            'minify' => false,
        ]);
    }
}
