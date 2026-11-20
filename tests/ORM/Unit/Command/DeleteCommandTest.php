<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Mockery as m;
use Testo\Expect;
use Testo\Test;

#[Test]
final class DeleteCommandTest
{
    private m\LegacyMockInterface|m\MockInterface|MapperInterface $mapper;

    public function testNoScope(): void
    {
        Expect::exception(CommandException::class);

        $this->mapper = m::mock(MapperInterface::class);
        $state = new State(Node::SCHEDULED_DELETE, []);
        $cmd = new Delete(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            $this->mapper,
        );

        $cmd->execute();
    }
}
