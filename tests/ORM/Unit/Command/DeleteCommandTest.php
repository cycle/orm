<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\Database\DatabaseInterface;

class DeleteCommandTest extends TestCase
{
    public function testNoScope(): void
    {
        $this->expectException(CommandException::class);

        $state = new State(Node::SCHEDULED_DELETE, []);
        $cmd = new Delete(
            m::mock(DatabaseInterface::class),
            'table',
            $state
        );

        $cmd->execute();
    }
}
