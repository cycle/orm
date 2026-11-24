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

    public function primaryCommandJoinsTheSequenceByDefault(): void
    {
        $primary = m::mock(TestInsert::class);

        Assert::same((new Sequence($primary))->getCommands(), [$primary]);
        Assert::same((new Sequence($primary, false))->getCommands(), []);
        Assert::same((new Sequence(null, false))->getCommands(), []);
    }

    public function sequenceIsExecutedWhenEveryCommandIs(): void
    {
        $done = m::mock(TestInsert::class);
        $done->allows('isExecuted')->andReturn(true);
        $pending = m::mock(TestInsert::class);
        $pending->allows('isExecuted')->andReturn(false);

        Assert::true((new Sequence())->addCommand($done)->isExecuted());
        Assert::false((new Sequence())->addCommand($done, $pending)->isExecuted());
    }

    public function nestedSequenceIsFlattened(): void
    {
        $first = m::mock(TestInsert::class);
        $second = m::mock(TestInsert::class);
        $third = m::mock(TestInsert::class);
        $command = (new Sequence())->addCommand($first, (new Sequence())->addCommand($second), $third);

        Assert::same(\iterator_to_array($command, false), [$first, $second, $third]);
        Assert::count($command, 3);
    }

    public function sequenceHasNoDatabase(): void
    {
        Assert::null((new Sequence(m::mock(TestInsert::class)))->getDatabase());
    }
}
