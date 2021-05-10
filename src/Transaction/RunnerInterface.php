<?php

declare(strict_types=1);

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;

interface RunnerInterface extends \Countable
{
    public function run(CommandInterface $command): void;

    /**
     * Complete/commit all executed changes. Must clean the state of the runner.
     */
    public function complete(): void;

    /**
     * Rollback all executed changes. Must clean the state of the runner.
     */
    public function rollback(): void;
}
