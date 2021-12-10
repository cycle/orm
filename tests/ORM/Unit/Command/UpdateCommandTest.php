<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\Query\UpdateQuery;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\Database\DatabaseInterface;

class UpdateCommandTest extends TestCase
{
    private m\LegacyMockInterface|m\MockInterface|MapperInterface $mapper;
    private Update $cmd;
    private State $state;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mapper = \Mockery::mock(MapperInterface::class);
        $this->state = new State(Node::SCHEDULED_UPDATE, ['foo' => 'bar']);

        $this->cmd = new Update(
            $this->db = m::mock(DatabaseInterface::class),
            'table',
            $this->state,
            $this->mapper,
            []
        );
    }

    public function testIsEmpty(): void
    {
        $this->assertTrue($this->cmd->isReady());
    }

    public function testHasData(): void
    {
        $this->state->register('name', 'new value');
        $this->assertTrue($this->cmd->hasData());
    }

    public function testHasDataAppendix(): void
    {
        $this->cmd->registerAppendix('name', 'new value');
        $this->assertFalse($this->cmd->hasData());
    }

    public function testHasDataColumn(): void
    {
        $this->cmd->registerColumn('name', 'new value');
        $this->assertTrue($this->cmd->hasData());
    }

    public function testScopeSetter(): void
    {
        $this->cmd->waitScope('key');
        $this->cmd->setScope('key', 'value');
        $this->assertSame(['key' => 'value'], $this->cmd->getScope());
        $this->assertTrue($this->cmd->isScopeReady());
    }

    public function testSetNullScope(): void
    {
        $this->cmd->waitScope('key');
        $this->cmd->setScope('key', null);
        $this->assertFalse($this->cmd->isScopeReady());
    }

    public function testExecuteWithNoScopeShouldThrowException(): void
    {
        $this->expectException(CommandException::class);

        $this->cmd->execute();
    }

    public function testExecuteWithAppendix()
    {
        $this->cmd->setScope('key', 'value');
        $this->cmd->registerAppendix('name', 'new value');

        $this->mapper->shouldReceive('mapColumns')
            ->with(['name' => 'new value'])
            ->andReturn(['baz' => 'bar']);

        $this->mapper->shouldReceive('uncast')
            ->with(['baz' => 'bar'])
            ->andReturn(['baz1' => 'bar']);

        $this->mapper->shouldReceive('mapColumns')
            ->with(['key' => 'value'])
            ->andReturn(['key' => 'new_value']);

        $this->db->shouldReceive('update')
            ->once()
            ->with('table', ['baz1' => 'bar'], ['key' => 'new_value'])
            ->andReturn($query = \Mockery::mock(UpdateQuery::class));

        $query->shouldReceive('run')->once()->andReturn(5);

        $this->cmd->execute();

        $this->assertSame(['foo' => 'bar', 'name' => 'new value'], $this->state->getData());
        $this->assertSame(5, $this->cmd->getAffectedRows());
    }

    public function testExecuteWithEmptyDataShouldNptRunQuwry()
    {
        $this->cmd->setScope('key', 'value');
        $this->cmd->registerAppendix('name', 'new value');

        $this->mapper->shouldReceive('mapColumns')
            ->with(['name' => 'new value'])
            ->andReturn();

        $this->cmd->execute();

        $this->assertSame(['foo' => 'bar', 'name' => 'new value'], $this->state->getData());
        $this->assertSame(0, $this->cmd->getAffectedRows());
    }
}
