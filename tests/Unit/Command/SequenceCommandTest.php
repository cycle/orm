<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Tests\Unit\Command\Helper\TestInsert;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class SequenceCommandTest
{
    public function testNestedCommands(): void
    {
        // todo test with embedded
        $command = new Sequence();

        $command->addCommand(m::mock(TestInsert::class));
        $command->addCommand(m::mock(TestInsert::class));

        $count = 0;
        foreach ($command as $sub) {
            Assert::instanceOf($sub, TestInsert::class);
            $count++;
        }

        Assert::same($count, 2);
        Assert::count($command, 2);
        Assert::count($command->getCommands(), 2);

        //Nothing
        $command->execute();
    }

    public function testNeverExecuted(): void
    {
        $command = new Sequence();
        Assert::true($command->isReady());
        Assert::true($command->isExecuted());
    }
}
