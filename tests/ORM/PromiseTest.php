<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;


use PHPUnit\Framework\TestCase;
use Spiral\ORM\Util\Promise;

class PromiseTest extends TestCase
{
    public function testPromise()
    {
        $p = new Promise(['key' => 'value'], function ($context) {
            return $context;
        });

        $this->assertFalse($p->__loaded());
        $this->assertSame(['key' => 'value'], $p->__scope());
        $this->assertSame(['key' => 'value'], $p->__resolve());
        $this->assertTrue($p->__loaded());
    }

    public function testPromiseNull()
    {
        $p = new Promise(['key' => 'value'], function ($context) {
            return null;
        });

        $this->assertFalse($p->__loaded());
        $this->assertSame(['key' => 'value'], $p->__scope());
        $this->assertSame(null, $p->__resolve());
        $this->assertTrue($p->__loaded());
    }
}