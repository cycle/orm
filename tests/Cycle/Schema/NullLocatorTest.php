<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Spiral\Cycle\Schema\NullLocator;

class NullLocatorTest extends TestCase
{
    public function testLocator()
    {
        $locator = new NullLocator();

        $this->assertSame([], $locator->getDeclarations());
    }
}