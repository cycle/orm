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
use Spiral\ORM\Command\ChainContextCommand;
use Spiral\ORM\Command\Database\InsertContextCommand;
use Spiral\ORM\Command\NullCommand;

class ChainCommandTest extends TestCase
{
    public function testNestedCommands()
    {
        $command = new ChainContextCommand();

        $command->addCommand(new NullCommand());
        $command->addCommand(new NullCommand());
        $command->addCommand(m::mock(InsertContextCommand::class));
        $command->addCommand(m::mock(InsertContextCommand::class));

        $count = 0;
        foreach ($command as $sub) {
            $this->assertInstanceOf(InsertContextCommand::class, $sub);
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
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $lead->expects('getPrimaryKey')->andReturn(1);

        $this->assertSame(1, $command->getPrimaryKey());
    }

    /**
     * @expectedException \Spiral\ORM\Exception\CommandException
     */
    public function testGetLeadingBad()
    {
        $command = new ChainContextCommand();
        $command->getPrimaryKey();
    }

    public function testGetContext()
    {
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $lead->shouldReceive('getContext')->andReturn(['hi']);

        $this->assertSame(['hi'], $command->getContext());
    }

    public function testAddContext()
    {
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $lead->shouldReceive('setContext')->with('name', 'value');

        $command->setContext('name', 'value');
        $this->assertTrue(true);
    }

    public function testPassCallbackExecute()
    {
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onExecute')->with($f);
        $command->onExecute($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackComplete()
    {
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onComplete')->with($f);
        $command->onComplete($f);
        $this->assertTrue(true);
    }

    public function testPassCallbackRollback()
    {
        $command = new ChainContextCommand();
        $command->addTargetCommand($lead = m::mock(InsertContextCommand::class));

        $f = function () {
        };

        $lead->shouldReceive('onRollback')->with($f);
        $command->onRollBack($f);
        $this->assertTrue(true);
    }
}