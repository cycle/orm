<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap\Tests\Treap\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\Treap\Command\ChainCommand;
use Spiral\Treap\Command\Database\InsertCommand;
use Spiral\Treap\Command\NullCommand;

class ChainCommandTest extends TestCase
{
    public function testNestedCommands()
    {
        $command = new ChainCommand();

        $command->addCommand(new NullCommand());
        $command->addCommand(new NullCommand());
        $command->addCommand(m::mock(InsertCommand::class));
        $command->addCommand(m::mock(InsertCommand::class));

        $count = 0;
        foreach ($command as $sub) {
            $this->assertInstanceOf(InsertCommand::class, $sub);
            $count++;
        }

        $this->assertSame(2, $count);

        //Nothing
        $command->execute();
        $command->complete();
        $command->rollBack();
    }

    public function testGetPrimaryKey()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $lead->expects('getPrimaryKey')->andReturn(1);

        $this->assertSame(1, $command->getPrimaryKey());
    }

    /**
     * @expectedException \Spiral\Treap\Exception\CommandException
     */
    public function testGetLeadingBad()
    {
        $command = new ChainCommand();
        $command->getPrimaryKey();
    }

    public function testIsEmpty()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $lead->shouldReceive('isEmpty')->andReturn(true);

        $this->assertSame(true, $command->isEmpty());
    }

    public function testGetContext()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $lead->shouldReceive('getContext')->andReturn(['hi']);

        $this->assertSame(['hi'], $command->getContext());
    }

    public function testAddContext()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $lead->shouldReceive('addContext')->with('name', 'value');

        $command->addContext('name', 'value');
        $this->assertTrue(true);
    }

    public function testPassCallbackExecute()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onExecute')->with($f);
        $command->onExecute($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackComplete()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onComplete')->with($f);
        $command->onComplete($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackRollback()
    {
        $command = new ChainCommand();
        $command->addTargetCommand($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onRollback')->with($f);
        $command->onRollBack($f);
        $this->assertTrue(true);
    }
}