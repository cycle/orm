<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\Database\Driver\DriverInterface;
use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\CompleteMethodInterface;
use Cycle\ORM\Command\RollbackMethodInterface;
use Cycle\ORM\Command\StoreCommandInterface;
use Cycle\ORM\Exception\RunnerException;
use Traversable;

/**
 * @internal
 */
final class Runner implements RunnerInterface
{
    private const MODE_IGNORE_TRANSACTION = 0;
    private const MODE_CONTINUE_TRANSACTION = 1;
    private const MODE_OPEN_TRANSACTION = 2;

    /** @var DriverInterface[] */
    private array $drivers = [];

    /** @var CommandInterface[] */
    private array $executed = [];

    private int $countExecuted = 0;

    private function __construct(
        private int $mode
    ) {
    }

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

            if (!\in_array($driver, $this->drivers, true)) {
                $this->useTransaction($driver);

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
        if ($this->mode === self::MODE_OPEN_TRANSACTION) {
            // Commit all of the open and normalized database transactions
            foreach (array_reverse($this->drivers) as $driver) {
                /** @var DriverInterface $driver */
                $driver->commitTransaction();
            }
        }

        // Other type of transaction to close
        foreach ($this->executed as $command) {
            if ($command instanceof CompleteMethodInterface) {
                $command->complete();
            }
        }
        $this->drivers = $this->executed = [];
    }

    public function rollback(): void
    {
        if ($this->mode === self::MODE_OPEN_TRANSACTION) {
            // Close all open and normalized database transactions
            foreach (array_reverse($this->drivers) as $driver) {
                /** @var DriverInterface $driver */
                $driver->rollbackTransaction();
            }
        }

        // Close all of external types of transactions (revert changes)
        foreach (array_reverse($this->executed) as $command) {
            if ($command instanceof RollbackMethodInterface) {
                $command->rollBack();
            }
        }

        $this->drivers = $this->executed = [];
    }

    /**
     * Create Runner in the 'create transaction' mode.
     * In this case the Runner will open new transaction for each used driver connection
     * and will close they on finish.
     */
    public static function openTransaction(): self
    {
        return new self(self::MODE_OPEN_TRANSACTION);
    }

    /**
     * Create Runner in the 'continue transaction' mode.
     * In this case the Runner won't begin transactions, you should do it previously manually.
     * In case when a transaction won't be opened the Runner will throw an Exception and stop Unit of Work.
     *
     * The 'continue transaction' mode also means the Runner WON'T commit or rollback opened transactions
     * on success or fail.
     * But commands that implement CompleteMethodInterface or RollbackMethodInterface will be called.
     */
    public static function continueTransaction(): self
    {
        return new self(self::MODE_CONTINUE_TRANSACTION);
    }

    /**
     * Create Runner in the 'ignore transaction' mode.
     * In this case the Runner won't begin/commit/rollback transactions and will ignore any transaction statuses.
     */
    public static function ignoreTransaction(): self
    {
        return new self(self::MODE_IGNORE_TRANSACTION);
    }

    private function useTransaction(DriverInterface $driver): void
    {
        if ($this->mode === self::MODE_IGNORE_TRANSACTION) {
            return;
        }

        if ($this->mode === self::MODE_CONTINUE_TRANSACTION) {
            if ($driver->getTransactionLevel() === 0) {
                throw new RunnerException(sprintf(
                    'The `%s` driver connection has no opened transaction.',
                    $driver->getType()
                ));
            }
            return;
        }

        $driver->beginTransaction();
    }
}
