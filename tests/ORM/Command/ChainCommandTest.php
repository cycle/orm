<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Command;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Spiral\ORM\Command\ChainCommand;
use Spiral\ORM\Command\Database\InsertCommand;
use Spiral\ORM\Command\NullCommand;

abstract class ChainCommandTest extends TestCase
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
        $command->addParent($lead = m::mock(InsertCommand::class));

        $lead->expects('getPrimaryKey')->andReturn(1);

        $this->assertSame(1, $command->getPrimaryKey());
    }

    /**
     * @expectedException \Spiral\ORM\Exception\CommandException
     */
    public function testGetLeadingBad()
    {
        $command = new ChainCommand();
        $command->getPrimaryKey();
    }

    public function testGetContext()
    {
        $command = new ChainCommand();
        $command->addParent($lead = m::mock(InsertCommand::class));

        $lead->shouldReceive('getContext')->andReturn(['hi']);

        $this->assertSame(['hi'], $command->getContext());
    }

    public function testAddContext()
    {
        $command = new ChainCommand();
        $command->addParent($lead = m::mock(InsertCommand::class));

        $lead->shouldReceive('setContext')->with('name', 'value');

        $command->setContext('name', 'value');
        $this->assertTrue(true);
    }

    public function testPassCallbackExecute()
    {
        $command = new ChainCommand();
        $command->addParent($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onExecute')->with($f);
        $command->onExecute($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackComplete()
    {
        $command = new ChainCommand();
        $command->addParent($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onComplete')->with($f);
        $command->onComplete($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackRollback()
    {
        $command = new ChainCommand();
        $command->addParent($lead = m::mock(InsertCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onRollback')->with($f);
        $command->onRollBack($f);
        $this->assertTrue(true);
    }
}