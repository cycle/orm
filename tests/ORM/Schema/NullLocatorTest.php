<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Schema;

use PHPUnit\Framework\TestCase;
use Spiral\ORM\Schema\NullLocator;

class NullLocatorTest extends TestCase
{
    public function testLocator()
    {
        $locator = new NullLocator();

        $this->assertSame([], $locator->getDeclarations());
    }
}