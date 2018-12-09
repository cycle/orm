<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\ORM\Node;

class StateTest extends TestCase
{
    public function testPush()
    {
        $s = new Node(Node::LOADED, [], "parent");

        $s->register('user_id', 1);

        $this->assertSame(1, $s->getData()['user_id']);
    }

    public function testForward()
    {
        $s = new Node(Node::LOADED, [], "parent");
        $c = new Node(Node::LOADED, [], "child");

        $s->listen('id', $c, 'user_id');
        $s->register('id', 1);

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefault()
    {
        $s = new Node(Node::LOADED, ['id' => 1], "parent");
        $c = new Node(Node::LOADED, [], "child");

        $s->listen('id', $c, 'user_id');

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefaultTrigger()
    {
        $s = new Node(Node::LOADED, ['id' => 1], "parent");
        $c = new Node(Node::LOADED, [], "child");

        $s->listen('id', $c, 'user_id', true);

        $this->assertSame(1, $c->getData()['user_id']);
    }
}