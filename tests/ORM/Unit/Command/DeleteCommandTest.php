<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\Database\DatabaseInterface;

class DeleteCommandTest extends TestCase
{
    private m\LegacyMockInterface|m\MockInterface|MapperInterface $mapper;

    public function testNoScope(): void
    {
        $this->expectException(CommandException::class);

        $this->mapper = \Mockery::mock(MapperInterface::class);
        $state = new State(Node::SCHEDULED_DELETE, []);
        $cmd = new Delete(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            $this->mapper
        );

        $cmd->execute();
    }
}
