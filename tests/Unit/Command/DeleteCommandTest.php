<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Query\DeleteQuery;
use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Mockery as m;
use Testo\Assert;
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

    public function executedDeletionMarksStateDeleted(): void
    {
        $state = new State(Node::SCHEDULED_DELETE, ['id' => 1]);
        $query = m::mock(DeleteQuery::class);
        $query->allows('run')->andReturn(1);
        $db = m::mock(DatabaseInterface::class);
        $db->allows('delete')->with('table', ['id' => 1])->andReturn($query);
        $cmd = new Delete($db, 'table', $state, null);
        $cmd->setScope('id', 1);

        $cmd->execute();

        Assert::same($state->getStatus(), Node::DELETED);
        Assert::true($cmd->isExecuted());
        Assert::same($cmd->getAffectedRows(), 1);
    }
}
