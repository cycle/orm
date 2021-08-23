<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\Database\DatabaseInterface;

class InsertCommandTest extends TestCase
{
    public function testDatabase(): void
    {
        $state = new State(Node::SCHEDULED_INSERT, []);
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            $state
        );

        $this->assertSame($db, $cmd->getDatabase());
    }

    public function testIsEmpty(): void
    {
        $state = new State(Node::SCHEDULED_INSERT, []);
        $cmd = new Insert(
            $db = m::mock(DatabaseInterface::class),
            'table',
            $state
        );

        $this->assertTrue($cmd->isReady());
    }
}
