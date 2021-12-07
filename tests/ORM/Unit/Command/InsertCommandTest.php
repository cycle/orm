<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\CompilerInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\Database\Injection\FragmentInterface;
use Cycle\Database\Query\QueryParameters;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\Tests\Fixtures\TestInsertCommand;
use Cycle\ORM\Tests\Fixtures\TestInsertCommandWithReturning;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class InsertCommandTest extends TestCase
{
    private Insert $cmd;
    private m\LegacyMockInterface|m\MockInterface|DatabaseInterface $db;
    private State $state;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cmd = new Insert(
            $this->db = m::mock(DatabaseInterface::class),
            'table',
            $this->state = new State(Node::SCHEDULED_INSERT, []),
            ['id'],
            'foo_id'
        );
    }

    public function testDatabase(): void
    {
        $this->assertSame($this->db, $this->cmd->getDatabase());
    }

    public function testIsReady(): void
    {
        $this->assertTrue($this->cmd->isReady());
    }

    public function testCommandWithoutReturningInterfaceShouldNotUseIt(): void
    {
        $table = 'table';

        $insertQuery = (new TestInsertCommand($table))->withDriver($driver = m::mock(DriverInterface::class), '');

        $driver->shouldReceive('getQueryCompiler')->once()->andReturn($compiler = m::mock(CompilerInterface::class));
        $driver->shouldReceive('execute')->once();
        $driver->shouldReceive('lastInsertID')->once()->andReturn(123);

        $compiler->shouldReceive('compile')->once()->withArgs(
            function (QueryParameters $params, string $prefix, FragmentInterface $fragment) {
                return true;
            }
        );

        $this->db->shouldReceive('insert')->once()->with($table)->andReturn($insertQuery);
        $this->cmd->execute();

        $this->assertSame(123, $this->state->getValue('id'));
    }

    public function testCommandWithReturningInterfaceWithoutPkColumnShouldNotUseIt()
    {
        $class = new \ReflectionClass($this->cmd);
        $property = $class->getProperty('pkColumn');
        $property->setAccessible(true);
        $property->setValue($this->cmd, null);

        $table = 'table';

        $this->db->shouldReceive('insert')
            ->once()
            ->with($table)
            ->andReturn($insertQuery = m::mock(TestInsertCommandWithReturning::class));

        $insertQuery->shouldReceive('values')->once()->andReturnSelf();
        $insertQuery->shouldReceive('run')->once()->andReturn(345);
        $this->cmd->execute();

        $this->assertSame(345, $this->state->getValue('id'));
    }

    public function testCommandWithReturningInterfaceShouldUseIt()
    {
        $table = 'table';

        $this->db->shouldReceive('insert')
            ->once()
            ->with($table)
            ->andReturn($insertQuery = m::mock(TestInsertCommandWithReturning::class));

        $insertQuery->shouldReceive('values')->once()->andReturnSelf();
        $insertQuery->shouldReceive('returning')->once()->with('foo_id')->andReturnSelf();
        $insertQuery->shouldReceive('run')->once()->andReturn(234);

        $this->cmd->execute();

        $this->assertSame(234, $this->state->getValue('id'));
    }
}
