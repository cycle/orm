<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\DatabaseCommand;
use Spiral\Cycle\Exception\TransactionException;
use Spiral\Cycle\Heap\Node;
use Spiral\Database\Driver\DriverInterface;

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
    private $known;

    /** @var array */
    private $persist = [];

    /** @var array */
    private $delete = [];

    /** @param ORMInterface $orm */
    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
        $this->known = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function persist($entity, int $mode = self::MODE_CASCADE)
    {
        if ($this->known->offsetExists($entity)) {
            return;
        }
        $this->known->offsetSet($entity, true);

        $this->persist[] = [$entity, $mode];
    }

    /**
     * {@inheritdoc}
     */
    public function delete($entity, int $mode = self::MODE_CASCADE)
    {
        if ($this->known->offsetExists($entity)) {
            return;
        }
        $this->known->offsetSet($entity, true);

        $this->delete[] = [$entity, $mode];
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
                    if ($do->isExecuted()) {
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
            // close all open and normalized database transactions
            foreach (array_reverse($drivers) as $driver) {
                /** @var DriverInterface $driver */
                $driver->rollbackTransaction();
            }

            // close all of external types of transactions (revert changes)
            foreach (array_reverse($executed) as $command) {
                /** @var CommandInterface $command */
                $command->rollBack();
            }

            // no calculations must be kept in node states, resetting
            // this will keep entity data as it was before transaction run
            $this->resetHeap();

            throw $e;
        } finally {
            if (empty($e)) {
                // we are ready to commit all changes to our representation layer
                $this->syncHeap();
            }
        }

        // commit all of the open and normalized database transactions
        foreach (array_reverse($drivers) as $driver) {
            /** @var DriverInterface $driver */
            $driver->commitTransaction();
        }

        foreach ($executed as $command) {
            // other type of transaction to close
            $command->complete();
        }

        // resetting the scope
        $this->persist = $this->delete = [];
        $this->known = new \SplObjectStorage();
    }

    /**
     * Sync all entity states with generated changes.
     */
    protected function syncHeap()
    {
        $heap = $this->orm->getHeap();
        foreach ($heap as $entity) {
            $node = $heap->get($entity);

            // marked as being deleted and has no external claims (GC like approach)
            if ($node->getStatus() == Node::SCHEDULED_DELETE && !$node->getState()->hasClaims()) {
                $heap->detach($entity);
                continue;
            }

            // sync the current entity data with newly generated data
            $this->orm->getMapper($entity)->hydrate($entity, $node->syncState());
        }
    }

    /**
     * Reset heap to it's initial state and remove all the changes.
     */
    protected function resetHeap()
    {
        $heap = $this->orm->getHeap();
        foreach ($heap as $entity) {
            $heap->get($entity)->resetState();
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
        foreach ($this->persist as $pair) {
            $commands[] = $this->orm->queueStore($pair[0], $pair[1]);
        }

        // other commands?

        foreach ($this->delete as $pair) {
            $commands[] = $this->orm->queueDelete($pair[0], $pair[1]);
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
                // command or command branch is not ready
                yield $command => null;
                continue;
            }

            if ($command instanceof \Traversable) {
                // deepening (cut-off on first not-ready level)
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
            // i miss you Go
            if (method_exists($command, '__toError')) {
                $errors[] = $command->__toError();
            } else {
                $errors[] = get_class($command);
            }
        }

        return join(', ', $errors);
    }
}