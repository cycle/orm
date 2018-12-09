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
use Spiral\ORM\Command\Branch\Nil;
use Spiral\ORM\Command\Branch\ContextSequence;
use Spiral\ORM\Command\Branch\Sequence;
use Spiral\ORM\Command\Database\Insert;

class SequenceCommandTest extends TestCase
{
    public function testNestedCommands()
    {
        $command = new Sequence();

        $command->addCommand(new Nil());
        $command->addCommand(new Nil());
        $command->addCommand(m::mock(Insert::class));
        $command->addCommand(m::mock(Insert::class));

        $count = 0;
        foreach ($command as $sub) {
            $this->assertInstanceOf(Insert::class, $sub);
            $count++;
        }

        $this->assertSame(2, $count);

        //Nothing
        $command->execute();
        $command->complete();
        $command->rollBack();
    }

    /**
     * @expectedException \Spiral\ORM\Exception\CommandException
     */
    public function testGetLeadingBad()
    {
        $command = new ContextSequence();
        $command->getContext();
    }

    public function testGetContext()
    {
        $command = new ContextSequence();
        $command->addPrimary($lead = m::mock(Insert::class));

        $lead->shouldReceive('getContext')->andReturn(['hi']);

        $this->assertSame(['hi'], $command->getContext());
    }
}