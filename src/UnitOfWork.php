<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Database\Driver\DriverInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Database\DatabaseCommand;
use Spiral\ORM\Command\DelayCommand;
use Spiral\ORM\Command\DelayedCommandInterface;
use Spiral\ORM\Exception\TransactionException;

class UnitOfWork implements TransactionInterface
{
    /**
     * @invisible
     * @var ORMInterface
     */
    private $orm;

    private $managed;
    // todo: do not store twice

    private $store = [];

    private $delete = [];

    /** @param ORMInterface $orm */
    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
        $this->managed = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function store($entity)
    {
        if ($this->managed->offsetExists($entity)) {
            return;
        }
        $this->managed->offsetSet($entity, true);

        $this->store[] = $entity;

        // todo: snapshotting
        $id = spl_object_hash($this);
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity)
    {
        if ($this->managed->offsetExists($entity)) {
            return;
        }
        $this->managed->offsetSet($entity, true);

        $this->delete[] = $entity;
    }

    /**
     * Return flattened list of commands.
     *
     * @return \Generator
     */
    protected function getCommands()
    {
        $id = spl_object_hash($this);

        $commands = [];
        foreach ($this->store as $entity) {
            $commands[] = $this->orm->getMapper($entity)->queueStore($entity, $id);
        }

        foreach ($this->delete as $entity) {
            $commands[] = $this->orm->getMapper($entity)->queueDelete($entity);
        }

        // todo: BRANCHING MUST BE MOVED INTO THE RUNCOMMAND!!!!
        foreach ($commands as $command) {
            if ($command instanceof \Traversable) {
                yield from $command;
            }

            yield $command;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function run()
    {
        $commands = $this->getCommands();
        $executed = $drivers = [];

        try {
            while (!empty($commands)) {
                $pending = [];
                $countExecuted = count($executed);

                foreach ($this->execute($commands, $drivers) as $done => $skip) {
                    if ($done != null) {
                        $executed[] = $done;
                    }

                    if ($skip != null) {
                        $pending[] = $skip;
                    }
                }

                if (count($executed) === $countExecuted) {
                    throw new TransactionException("Unable to complete: " . join(", ", $pending));
                }

                $commands = $pending;
            }

        } catch (\Throwable $e) {
            foreach (array_reverse($drivers) as $driver) {
                /** @var DriverInterface $driver */
                $driver->rollbackTransaction();
            }

            foreach (array_reverse($executed) as $command) {
                /** @var CommandInterface $command */
                $command->rollBack();
            }

            throw $e;
        }

        foreach (array_reverse($drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->commitTransaction();
        }

        foreach ($executed as $command) {
            //This is the point when entity will get related PK and FKs filled
            $command->complete();
        }

        $this->store = [];
        $this->delete = [];
    }

    /**
     * @param CommandInterface $command
     * @param array            $drivers
     */
    private function beginTransaction(CommandInterface $command, array &$drivers)
    {
        if ($command instanceof DatabaseCommand) {
            $driver = $command->getDatabase()->getDriver();

            if (!empty($driver) && !in_array($driver, $drivers, true)) {
                $driver->beginTransaction();
                $drivers[] = $driver;
            }
        }
    }

    /**
     * Execute and split array of commands into two subsets: executed and pending.
     *
     * @param iterable $commands
     * @param array    $drivers
     * @return \Generator
     */
    private function execute(iterable $commands, array &$drivers): \Generator
    {
        foreach ($commands as $command) {
            if ($command instanceof DelayedCommandInterface) {
                if ($command->isDelayed()) {
                    yield null => $command;
                    continue;
                }

                if ($command instanceof DelayCommand && $command->getParent() instanceof \Traversable) {
                    // todo: make it better
                    yield from $this->execute($command->getParent(), $drivers);
                    continue;
                }
            }

            $this->beginTransaction($command, $drivers);
            $command->execute();

            yield $command => null;
        }
    }
}