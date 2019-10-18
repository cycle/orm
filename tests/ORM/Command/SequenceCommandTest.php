<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Branch\ContextSequence;
use Cycle\ORM\Command\Branch\Nil;
use Cycle\ORM\Command\Branch\Sequence;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Cycle\ORM\Tests\Command\Helper\TestInsert;

class SequenceCommandTest extends TestCase
{
    public function testNestedCommands(): void
    {
        $command = new Sequence();

        $command->addCommand(new Nil());
        $command->addCommand(new Nil());
        $command->addCommand(m::mock(TestInsert::class));
        $command->addCommand(m::mock(TestInsert::class));

        $count = 0;
        foreach ($command as $sub) {
            $this->assertInstanceOf(TestInsert::class, $sub);
            $count++;
        }

        $this->assertSame(2, $count);
        $this->assertSame(2, count($command));
        $this->assertCount(2, $command->getCommands());

        //Nothing
        $command->execute();
        $command->complete();
        $command->rollBack();
    }

    public function testNeverExecuted(): void
    {
        $command = new Sequence();
        $this->assertTrue($command->isReady());
        $this->assertFalse($command->isExecuted());
    }

    /**
     * @expectedException \Cycle\ORM\Exception\CommandException
     */
    public function testGetLeadingBad(): void
    {
        $command = new ContextSequence();
        $command->getContext();
    }

    public function testGetContext(): void
    {
        $command = new ContextSequence();
        $command->addPrimary($lead = m::mock(TestInsert::class));

        $lead->shouldReceive('getContext')->andReturn(['hi']);

        $this->assertSame(['hi'], $command->getContext());
    }
}
