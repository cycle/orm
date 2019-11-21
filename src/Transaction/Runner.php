<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\DatabaseCommand;
use Spiral\Database\Driver\DriverInterface;

final class Runner implements RunnerInterface
{
    /** @var DriverInterface[] */
    private $drivers = [];

    /** @var CommandInterface[] */
    private $executed = [];

    /** @var int */
    private $countExecuted = 0;

    /**
     * @inheritdoc
     */
    public function run(CommandInterface $command): void
    {
        // found the same link from multiple branches
        if ($command->isExecuted()) {
            $this->countExecuted++;
            return;
        }

        if ($command instanceof DatabaseCommand && !empty($command->getDatabase())) {
            $driver = $command->getDatabase()->getDriver();

            if ($driver !== null && !in_array($driver, $this->drivers, true)) {
                $driver->beginTransaction();
                $this->drivers[] = $driver;
            }
        }

        $command->execute();
        $this->countExecuted++;
        $this->executed[] = $command;
    }

    /**
     * @inheritdoc
     */
    public function count(): int
    {
        return $this->countExecuted;
    }

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
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
