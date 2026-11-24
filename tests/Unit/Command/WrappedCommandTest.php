<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Command;

use Cycle\Database\DatabaseInterface;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Command\Special\WrappedCommand;
use Cycle\ORM\Command\Special\WrappedStoreCommand;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
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

    public function wrapperReportsTheWrappedCommandState(): void
    {
        $inner = m::mock(CommandInterface::class);
        $inner->allows('isReady')->andReturn(false);
        $inner->allows('isExecuted')->andReturn(true);

        $command = WrappedCommand::wrapCommand($inner);

        Assert::false($command->isReady());
        Assert::true($command->isExecuted());
        Assert::same($command->getCommand(), $inner);
    }

    public function updateWrapperPassesDataToTheUpdate(): void
    {
        $db = m::mock(DatabaseInterface::class);
        $state = new State(Node::MANAGED, ['id' => 1, 'name' => 'old']);

        $command = WrappedStoreCommand::createUpdate($db, 'users', $state, null, ['id']);
        $command->registerColumn('type', 'admin');
        $command->registerAppendix('name', 'new');

        $update = $command->getCommand();
        Assert::instanceOf($update, Update::class);
        Assert::false($command->isReady());
        Assert::true($command->hasData());
        Assert::same($update->getStoreData(), ['type' => 'admin', 'name' => 'new']);
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
