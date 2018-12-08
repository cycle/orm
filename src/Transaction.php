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
use Spiral\ORM\Command\DatabaseCommand;
use Spiral\ORM\Exception\TransactionException;

/**
 * Transaction provides ability to define set of entities to be stored or deleted within one transaction. Transaction
 * can operate as UnitOfWork. Multiple transactions can co-exists in one application.
 *
 * Internally, upon "run", transaction will request mappers to generate graph of linked commands to create, update or
 * delete entities.
 */
final class Transaction implements TransactionInterface
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
        $executed = $drivers = [];

        try {
            $commands = $this->initCommands();

            while (!empty($commands)) {
                $pending = [];
                $countExecuted = count($executed);

                foreach ($this->sort($commands) as $wait => $do) {
                    if ($wait != null) {
                        if (in_array($wait, $pending, true)) {
                            continue;
                        }

                        $pending[] = $wait;
                        continue;
                    }

                    // found same link from multiple branches
                    if (in_array($do, $executed, true)) {
                        $countExecuted++;
                        continue;
                    }

                    $this->beginTransaction($do, $drivers);
                    $do->execute();

                    $executed[] = $do;
                }

                if (count($executed) === $countExecuted && !empty($pending)) {
                    throw new TransactionException("Unable to complete: " . $this->listCommands($pending));
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

            $this->resetHeap();

            throw $e;
        } finally {
            if (empty($e)) {
                $this->syncHeap();
            }
        }

        foreach (array_reverse($drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->commitTransaction();
        }

        foreach ($executed as $command) {
            // deliver all generated values to the linked entities
            $command->complete();
        }

        // resetting the scope
        $this->store = [];
        $this->delete = [];
        $this->managed = new \SplObjectStorage();
    }

    protected function syncHeap()
    {
        foreach ($this->orm->getHeap() as $entity) {
            $state = $this->orm->getHeap()->get($entity);
            $state->setCommand(null);
            $state->resetVisited();

            $state->setStatus(Point::LOADED);
            $this->orm->getMapper($entity)->hydrate($entity, $state->getData());
        }
    }

    /**
     * Reset heap to it's initial state and remove all the changes.
     */
    protected function resetHeap()
    {
        foreach ($this->orm->getHeap() as $entity) {
            // todo: need better reset?
            $this->orm->getHeap()->get($entity)->resetVisited();
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
            $commands[] = $this->orm->queueStore($entity);
        }

        // add custom commands?

        foreach ($this->delete as $entity) {
            $commands[] = $this->orm->queueDelete($entity);
        }

        return $commands;
    }

    /**
     * @param CommandInterface $command
     * @param array            $drivers
     */
    private function beginTransaction(CommandInterface $command, array &$drivers)
    {
        if ($command instanceof DatabaseCommand && !empty($command->getDatabase())) {
            $driver = $command->getDatabase()->getDriver();

            if (!empty($driver) && !in_array($driver, $drivers, true)) {
                $driver->beginTransaction();
                $drivers[] = $driver;
            }
        }
    }

    /**
     * Fetch commands which are ready for the execution. Provide ready commands
     * as generated value and delayed commands as the key.
     *
     * @param iterable $commands
     * @return \Generator
     */
    protected function sort(iterable $commands): \Generator
    {
        /** @var CommandInterface $command */
        foreach ($commands as $command) {
            if (!$command->isReady()) {
                yield $command => null;
                continue;
            }

            if ($command instanceof \Traversable) {
                yield from $this->sort($command);
                continue;
            }

            yield null => $command;
        }
    }

    /**
     * @param array $commands
     * @return string
     */
    private function listCommands(array $commands): string
    {
        $errors = [];
        foreach ($commands as $command) {
            if (method_exists($command, '__toString')) {
                $errors[] = $command->__toString();
            } else {
                $errors[] = get_class($command);
            }
        }

        return join(', ', $errors);
    }
}