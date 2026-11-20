<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Special\WrappedCommand;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class WrappedCommandTest
{
    /**
     * Public: callbacks run in the scope of the wrapper class.
     *
     * @var list<string>
     */
    public static array $log = [];

    public function callbacksRunAroundTheCommand(): void
    {
        self::$log = [];
        $command = WrappedCommand::wrapCommand($this->command())
            ->withBeforeExecution(static fn() => WrappedCommandTest::$log[] = 'before')
            ->withAfterExecution(static fn() => WrappedCommandTest::$log[] = 'after');

        $command->execute();

        Assert::same(self::$log, ['before', 'execute', 'after']);
    }

    public function callbacksDoNotChangeTheOriginalCommand(): void
    {
        self::$log = [];
        $original = WrappedCommand::wrapCommand($this->command());

        $original->withBeforeExecution(static fn() => WrappedCommandTest::$log[] = 'before');
        $original->withAfterExecution(static fn() => WrappedCommandTest::$log[] = 'after');
        $original->execute();

        Assert::same(self::$log, ['execute']);
    }

    private function command(): CommandInterface
    {
        $command = m::mock(CommandInterface::class);
        $command->allows('execute')->andReturnUsing(static function (): void {
            self::$log[] = 'execute';
        });
        return $command;
    }
}
