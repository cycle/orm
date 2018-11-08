<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Treap\Schema;

use PHPUnit\Framework\TestCase;
use Spiral\Treap\Schema\NullLocator;

class NullLocatorTest extends TestCase
{
    public function testLocator()
    {
        $locator = new NullLocator();

        $this->assertSame([], $locator->getDeclarations());
    }
}