<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests;

use PHPUnit\Framework\TestCase;
use Spiral\ORM\State;

class StateTest extends TestCase
{
    public function testPush()
    {
        $s = new State(State::LOADED, [], "parent");

        $s->push('user_id', 1);

        $this->assertSame(1, $s->getData()['user_id']);
    }

    public function testForward()
    {
        $s = new State(State::LOADED, [], "parent");
        $c = new State(State::LOADED, [], "child");

        $s->pull('id', $c, 'user_id');
        $s->push('id', 1);

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefault()
    {
        $s = new State(State::LOADED, ['id' => 1], "parent");
        $c = new State(State::LOADED, [], "child");

        $s->pull('id', $c, 'user_id');

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefaultTrigger()
    {
        $s = new State(State::LOADED, ['id' => 1], "parent");
        $c = new State(State::LOADED, [], "child");

        $s->pull('id', $c, 'user_id', true);

        $this->assertSame(1, $c->getData()['user_id']);
    }
}