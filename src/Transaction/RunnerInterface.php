<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Transaction;

use Cycle\ORM\Command\CommandInterface;

interface RunnerInterface extends \Countable
{
    /**
     * @param CommandInterface $command
     */
    public function run(CommandInterface $command);

    /**
     * Complete/commit all executed changes. Must clean the state of the runner.
     */
    public function complete();

    /**
     * Rollback all executed changes. Must clean the state of the runner.
     */
    public function rollback();
}