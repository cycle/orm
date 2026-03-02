<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\BelongsToMorphedLoadOptions;
use Cycle\ORM\Select\Options\JoinableLoadOptions;
use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class BelongsToMorphedLoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
    }

    public function testExtendsLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertInstanceOf(LoadOptions::class, $options);
    }

    public function testDoesNotExtendJoinableLoadOptions(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertNotInstanceOf(JoinableLoadOptions::class, $options);
    }

    public function testDoesNotHaveMethodProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertFalse(property_exists($options, 'method'));
    }

    public function testDoesNotHaveAsProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertFalse(property_exists($options, 'as'));
    }

    public function testDoesNotHaveUsingProperty(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertFalse(property_exists($options, 'using'));
    }

    public function testCustomScope(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $options = new BelongsToMorphedLoadOptions(scope: $scope);

        $this->assertSame($scope, $options->scope);
    }

    public function testScopeDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false);

        $this->assertFalse($options->scope);
    }

    public function testMinifyDisabled(): void
    {
        $options = new BelongsToMorphedLoadOptions(minify: false);

        $this->assertFalse($options->minify);
    }

    public function testToArrayDefaults(): void
    {
        $options = new BelongsToMorphedLoadOptions();

        $this->assertSame([
            'scope' => true,
            'minify' => true,
        ], $options->toArray());
    }

    public function testToArrayCustom(): void
    {
        $options = new BelongsToMorphedLoadOptions(scope: false, minify: false);

        $this->assertSame([
            'scope' => false,
            'minify' => false,
        ], $options->toArray());
    }
}
