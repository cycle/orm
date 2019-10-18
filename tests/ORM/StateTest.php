<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests;

use Cycle\ORM\Heap\Node;
use PHPUnit\Framework\TestCase;

class StateTest extends TestCase
{
    public function testPush(): void
    {
        $s = new Node(Node::MANAGED, [], 'parent');

        $s->register('user_id', 1);

        $this->assertSame(1, $s->getData()['user_id']);
    }

    public function testForward(): void
    {
        $s = new Node(Node::MANAGED, [], 'parent');
        $c = new Node(Node::MANAGED, [], 'child');

        $s->forward('id', $c, 'user_id');
        $s->register('id', 1);

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefault(): void
    {
        $s = new Node(Node::MANAGED, ['id' => 1], 'parent');
        $c = new Node(Node::MANAGED, [], 'child');

        $s->forward('id', $c, 'user_id');

        $this->assertSame(1, $c->getData()['user_id']);
    }

    public function testForwardDefaultTrigger(): void
    {
        $s = new Node(Node::MANAGED, ['id' => 1], 'parent');
        $c = new Node(Node::MANAGED, [], 'child');

        $s->forward('id', $c, 'user_id', true);

        $this->assertSame(1, $c->getData()['user_id']);
    }
}
