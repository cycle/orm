<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Exception\CommandException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\Database\DatabaseInterface;

class UpdateCommandTest extends TestCase
{
    public function testIsEmpty(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, []);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            []
        );

        $this->assertTrue($cmd->isReady());
    }

    public function testHasData(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, ['name' => 'value']);
        $state->register('name', 'new value');
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            []
        );

        $this->assertTrue($cmd->hasData());
    }

    public function testHasDataAppendix(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, ['name' => 'value']);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            [],
        );
        $cmd->registerAppendix('name', 'new value');

        $this->assertFalse($cmd->hasData());
    }

    public function testHasDataColumn(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, ['name' => 'value']);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            [],
        );
        $cmd->registerColumn('name', 'new value');

        $this->assertTrue($cmd->hasData());
    }

    public function testScopeSetter(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, []);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            []
        );
        $cmd->waitScope('key');

        $cmd->setScope('key', 'value');
        $this->assertSame(['key' => 'value'], $cmd->getScope());
        $this->assertTrue($cmd->isScopeReady());
    }

    public function testSetNullScope(): void
    {
        $state = new State(Node::SCHEDULED_UPDATE, []);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            []
        );
        $cmd->waitScope('key');

        $cmd->setScope('key', null);
        $this->assertFalse($cmd->isScopeReady());
    }

    public function testNoScope(): void
    {
        $this->expectException(CommandException::class);

        $state = new State(Node::SCHEDULED_UPDATE, []);
        $cmd = new Update(
            m::mock(DatabaseInterface::class),
            'table',
            $state,
            []
        );

        $cmd->execute();
    }
}
