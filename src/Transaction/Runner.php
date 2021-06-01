<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\DatabaseCommand;
use Spiral\Database\Driver\DriverInterface;
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
        foreach ($command instanceof Traversable ? $command : [$command] as $cmd) {
            // found the same link from multiple branches
            if ($cmd->isExecuted()) {
                $this->countExecuted++;
                return;
            }

            if ($cmd instanceof DatabaseCommand && $cmd->getDatabase() !== null) {
                $driver = $cmd->getDatabase()->getDriver();

                if ($driver !== null && !in_array($driver, $this->drivers, true)) {
                    $driver->beginTransaction();
                    $this->drivers[] = $driver;
                }
            }

            $cmd->execute();
        }
        $this->countExecuted++;
        $this->executed[] = $command;
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
            $command->complete();
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
            /** @var CommandInterface $command */
            $command->rollBack();
        }

        $this->countExecuted = 0;
        $this->drivers = $this->executed = [];
    }
}
