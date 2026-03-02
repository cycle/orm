<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Select\Options;

use Cycle\ORM\Select\Options\LoadOptions;
use Cycle\ORM\Select\ScopeInterface;
use PHPUnit\Framework\TestCase;

final class LoadOptionsTest extends TestCase
{
    public function testDefaults(): void
    {
        $options = new LoadOptions();

        $this->assertTrue($options->scope);
        $this->assertTrue($options->minify);
    }

    public function testCustomScope(): void
    {
        $scope = $this->createMock(ScopeInterface::class);
        $options = new LoadOptions(scope: $scope);

        $this->assertSame($scope, $options->scope);
    }

    public function testScopeDisabled(): void
    {
        $options = new LoadOptions(scope: false);

        $this->assertFalse($options->scope);
    }

    public function testMinifyDisabled(): void
    {
        $options = new LoadOptions(minify: false);

        $this->assertFalse($options->minify);
    }

    public function testToArray(): void
    {
        $options = new LoadOptions(scope: false, minify: false);

        $result = $options->toArray();

        $this->assertSame(['scope' => false, 'minify' => false], $result);
    }

    public function testInheritance(): void
    {
        $options = new LoadOptions();

        $this->assertNotInstanceOf(\Cycle\ORM\Select\Options\JoinableLoadOptions::class, $options);
    }
}
