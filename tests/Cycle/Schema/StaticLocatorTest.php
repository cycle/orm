<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Schema;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Schema\EntityInterface;
use Spiral\Cycle\Schema\StaticLocator;

class StaticLocatorTest extends TestCase
{
    public function testLocator()
    {
        $locator = new StaticLocator();
        $locator->add(m::mock(EntityInterface::class));

        $this->assertCount(1, $locator->getDeclarations());
    }
}