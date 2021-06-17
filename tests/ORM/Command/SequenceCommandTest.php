<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Command;

use Cycle\ORM\Command\Branch\Sequence;
use Cycle\ORM\Tests\Command\Helper\TestInsert;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SequenceCommandTest extends TestCase
{
    public function testNestedCommands(): void
    {
        $command = new Sequence();

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
}
