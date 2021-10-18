<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Tests\Unit\Command\Helper\TestInsert;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SequenceCommandTest extends TestCase
{
    public function testNestedCommands(): void
    {
        // todo test with embedded
        $command = new Sequence();

        $command->addCommand(m::mock(TestInsert::class));
        $command->addCommand(m::mock(TestInsert::class));

        $count = 0;
        foreach ($command as $sub) {
            $this->assertInstanceOf(TestInsert::class, $sub);
            $count++;
        }

        $this->assertSame(2, $count);
        $this->assertCount(2, $command);
        $this->assertCount(2, $command->getCommands());

        //Nothing
        $command->execute();
    }

    public function testNeverExecuted(): void
    {
        $command = new Sequence();
        $this->assertTrue($command->isReady());
        $this->assertTrue($command->isExecuted());
    }
}
