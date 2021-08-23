<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\CompleteMethodInterface;
use Cycle\ORM\Command\RollbackMethodInterface;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\Database\Driver\DriverInterface;
use Traversable;

final class Runner implements RunnerInterface
{
    /** @var DriverInterface[] */
    private array $drivers = [];

    /** @var CommandInterface[] */
    private array $executed = [];

    private int $countExecuted = 0;

    public function run(CommandInterface $command): void
    {
        if ($command instanceof Traversable) {
            foreach ($command as $cmd) {
                $this->run($cmd);
            }
            return;
        }
        // found the same link from multiple branches
        if ($command->isExecuted()) {
            $this->countExecuted++;
            return;
        }
        if ($command instanceof StoreCommandInterface && !$command->hasData()) {
            return;
        }

        if ($command->getDatabase() !== null) {
            $driver = $command->getDatabase()->getDriver();

            if ($driver !== null && !in_array($driver, $this->drivers, true)) {
                $driver->beginTransaction();
                $this->drivers[] = $driver;
            }
        }

        $command->execute();
        $this->countExecuted++;

        if ($command instanceof CompleteMethodInterface || $command instanceof RollbackMethodInterface) {
            $this->executed[] = $command;
        }
    }

    public function count(): int
    {
        return $this->countExecuted;
    }

    public function complete(): void
    {
        // commit all of the open and normalized database transactions
        foreach (array_reverse($this->drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->commitTransaction();
        }

        // other type of transaction to close
        foreach ($this->executed as $command) {
            if ($command instanceof CompleteMethodInterface) {
                $command->complete();
            }
        }

        $this->countExecuted = 0;
        $this->drivers = $this->executed = [];
    }

    public function rollback(): void
    {
        // close all open and normalized database transactions
        foreach (array_reverse($this->drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->rollbackTransaction();
        }

        // close all of external types of transactions (revert changes)
        foreach (array_reverse($this->executed) as $command) {
            if ($command instanceof RollbackMethodInterface) {
                $command->rollBack();
            }
        }

        $this->countExecuted = 0;
        $this->drivers = $this->executed = [];
    }
}
