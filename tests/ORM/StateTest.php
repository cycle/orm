<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\ORM\Point;

class StateTest extends TestCase
{
    public function testPush()
    {
        $s = new Point(Point::LOADED, [], "parent");

        $s->push('user_id', 1);

        $this->assertSame(1, $s->getData()['user_id']);
    }

    public function testForward()
    {
        $s = new Point(Point::LOADED, [], "parent");
        $c = new Point(Point::LOADED, [], "child");

        $s->pull('id', $c, 'user_id');
        $s->push('id', 1);

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefault()
    {
        $s = new Point(Point::LOADED, ['id' => 1], "parent");
        $c = new Point(Point::LOADED, [], "child");

        $s->pull('id', $c, 'user_id');

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefaultTrigger()
    {
        $s = new Point(Point::LOADED, ['id' => 1], "parent");
        $c = new Point(Point::LOADED, [], "child");

        $s->pull('id', $c, 'user_id', true);

        $this->assertSame(1, $c->getData()['user_id']);
    }
}