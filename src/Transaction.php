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
use Spiral\ORM\Exception\TransactionException;

class Transaction implements TransactionInterface
{
    /** @var ORMInterface */
    private $orm;

    /** @var \SplObjectStorage */
    private $managed;

    /** @var array */
    private $store = [];

    /** @var array */
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
     * {@inheritdoc}
     */
    public function run()
    {
        $commands = $this->initCommands();
        $executed = $drivers = [];

        try {
            while (!empty($commands)) {
                $pending = [];
                $countExecuted = count($executed);

                foreach ($this->reduce($commands) as $do => $wait) {
                    if ($wait != null) {
                        $pending[] = $wait;
                        continue;
                    }

                    $this->beginTransaction($do, $drivers);
                    $do->execute();

                    $executed[] = $do;
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
            // deliver all generated values to the linked entities
            $command->complete();
        }

        $this->store = [];
        $this->delete = [];
        $this->managed = new \SplObjectStorage();
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
     * Return flattened list of commands required to store and delete associated entities.
     *
     * @return array
     */
    protected function initCommands(): array
    {
        $commands = [];
        foreach ($this->store as $entity) {
            $commands[] = $this->orm->getMapper($entity)->queueStore($entity);
        }

        foreach ($this->delete as $entity) {
            $commands[] = $this->orm->getMapper($entity)->queueDelete($entity);
        }

        return $commands;
    }

    /**
     * Fetch commands ready for the execution. Generate ready commands as generated key and
     * delayed commands as value.
     *
     * @param iterable $commands
     * @return \Generator
     */
    protected function reduce(iterable $commands): \Generator
    {
        /** @var CommandInterface $command */
        foreach ($commands as $command) {
            if (!$command->isReady()) {
                yield null => $command;
                continue;
            }

            if ($command instanceof \Traversable) {
                yield from $this->reduce($command);
            }

            yield $command => null;
        }
    }
}