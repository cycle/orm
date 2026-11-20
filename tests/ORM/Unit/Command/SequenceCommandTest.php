<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Tests\Unit\Command\Helper\TestInsert;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class SequenceCommandTest
{
    #[\Testo\Test]
    public function testNestedCommands(): void
    {
        // todo test with embedded
        $command = new Sequence();

        $command->addCommand(m::mock(TestInsert::class));
        $command->addCommand(m::mock(TestInsert::class));

        $count = 0;
        foreach ($command as $sub) {
            \Testo\Assert::instanceOf($sub, TestInsert::class);
            $count++;
        }

        \Testo\Assert::same($count, 2);
        \Testo\Assert::count($command, 2);
        \Testo\Assert::count($command->getCommands(), 2);

        //Nothing
        $command->execute();
    }

    #[\Testo\Test]
    public function testNeverExecuted(): void
    {
        $command = new Sequence();
        \Testo\Assert::true($command->isReady());
        \Testo\Assert::true($command->isExecuted());
    }
}
