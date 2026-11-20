<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Unit\Transaction;

use Cycle\Database\DatabaseInterface;
use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\CompleteMethodInterface;
use Cycle\ORM\Command\RollbackMethodInterface;
use Cycle\ORM\Command\Special\Sequence;
use Cycle\ORM\Transaction\Runner;
use Mockery as m;
use Testo\Assert;
use Testo\Test;

#[Test]
final class RunnerTest
{
    /** @var list<string> */
    private array $log = [];

    public function executedCommandIsCountedButNotRunAgain(): void
    {
        $this->log = [];
        $command = $this->command('a', executed: true);
        $runner = Runner::outerTransaction(false);

        $runner->run($command);

        Assert::same($this->log, []);
        Assert::same($runner->count(), 1);
    }

    public function everyExecutedCommandIsCounted(): void
    {
        $this->log = [];
        $runner = Runner::outerTransaction(false);

        $runner->run($this->command('a'));
        $runner->run($this->command('b'));

        Assert::same($this->log, ['execute a', 'execute b']);
        Assert::same($runner->count(), 2);
    }

    public function sequenceIsCountedByItsCommands(): void
    {
        $this->log = [];
        $runner = Runner::outerTransaction(false);

        $runner->run((new Sequence())->addCommand($this->command('a'), $this->command('b')));

        Assert::same($this->log, ['execute a', 'execute b']);
        Assert::same($runner->count(), 2);
    }

    public function completeCallsCommandsThatSupportIt(): void
    {
        $this->log = [];
        $runner = Runner::outerTransaction(false);
        $runner->run($this->command('plain'));
        $runner->run($this->completable('c'));
        $runner->run($this->rollbackable('r'));
        $runner->run($this->both('b'));

        $runner->complete();

        Assert::same(\array_slice($this->log, 4), ['complete c', 'complete b']);
    }

    public function rollbackCallsCommandsInReverseOrder(): void
    {
        $this->log = [];
        $runner = Runner::outerTransaction(false);
        $runner->run($this->rollbackable('r1'));
        $runner->run($this->completable('c'));
        $runner->run($this->both('b'));
        $runner->run($this->rollbackable('r2'));

        $runner->rollback();

        Assert::same(\array_slice($this->log, 4), ['rollback r2', 'rollback b', 'rollback r1']);
    }

    public function innerTransactionCommitsDriversInReverseOrder(): void
    {
        $this->log = [];
        $runner = Runner::innerTransaction();
        $runner->run($this->command('a', $this->database('d1')));
        $runner->run($this->command('b', $this->database('d2')));

        $runner->complete();

        Assert::same($this->log, ['begin d1', 'execute a', 'begin d2', 'execute b', 'commit d2', 'commit d1']);
    }

    public function innerTransactionRollsDriversBackInReverseOrder(): void
    {
        $this->log = [];
        $runner = Runner::innerTransaction();
        $runner->run($this->command('a', $this->database('d1')));
        $runner->run($this->command('b', $this->database('d2')));

        $runner->rollback();

        Assert::same(\array_slice($this->log, 4), ['rollback d2', 'rollback d1']);
    }

    private function database(string $name): DatabaseInterface
    {
        $driver = m::mock(DriverInterface::class);
        $driver->allows('beginTransaction')->andReturnUsing(function () use ($name): bool {
            $this->log[] = "begin $name";
            return true;
        });
        $driver->allows('commitTransaction')->andReturnUsing(function () use ($name): bool {
            $this->log[] = "commit $name";
            return true;
        });
        $driver->allows('rollbackTransaction')->andReturnUsing(function () use ($name): bool {
            $this->log[] = "rollback $name";
            return true;
        });
        $db = m::mock(DatabaseInterface::class);
        $db->allows('getDriver')->andReturn($driver);
        return $db;
    }

    private function command(string $name, ?DatabaseInterface $db = null, bool $executed = false): CommandInterface
    {
        $command = m::mock(CommandInterface::class);
        $this->stub($command, $name, $db, $executed);
        return $command;
    }

    private function completable(string $name): CompleteMethodInterface
    {
        $command = m::mock(CompleteMethodInterface::class);
        $this->stub($command, $name);
        $command->allows('complete')->andReturnUsing(function () use ($name): void {
            $this->log[] = "complete $name";
        });
        return $command;
    }

    private function rollbackable(string $name): RollbackMethodInterface
    {
        $command = m::mock(RollbackMethodInterface::class);
        $this->stub($command, $name);
        $command->allows('rollBack')->andReturnUsing(function () use ($name): void {
            $this->log[] = "rollback $name";
        });
        return $command;
    }

    private function both(string $name): CompleteMethodInterface&RollbackMethodInterface
    {
        $command = m::mock(CompleteMethodInterface::class, RollbackMethodInterface::class);
        $this->stub($command, $name);
        $command->allows('complete')->andReturnUsing(function () use ($name): void {
            $this->log[] = "complete $name";
        });
        $command->allows('rollBack')->andReturnUsing(function () use ($name): void {
            $this->log[] = "rollback $name";
        });
        return $command;
    }

    private function stub(m\MockInterface $command, string $name, ?DatabaseInterface $db = null, bool $executed = false): void
    {
        $command->allows('isExecuted')->andReturn($executed);
        $command->allows('getDatabase')->andReturn($db);
        $command->allows('execute')->andReturnUsing(function () use ($name): void {
            $this->log[] = "execute $name";
        });
    }
}
