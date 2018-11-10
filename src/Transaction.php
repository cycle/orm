<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;

class Transaction implements TransactionInterface
{
    /** @var ORMInterface */
    private $orm;

    /*** @var CommandInterface[] */
    private $commands = [];

    /** @param ORMInterface $orm */
    public function __construct(ORMInterface $orm)
    {
        $this->orm = $orm;
    }

    // todo: modes!!
    public function store($entity)
    {
        // todo: what to do with relmap
        $this->orm->getMapper(get_class($entity));
    }

    public function delete($entity)
    {
        // todo: what to do with relmap
        $this->orm->getMapper(get_class($entity));
    }

    /**
     * {@inheritdoc}
     */
    public function addCommand(CommandInterface $command)
    {
        $this->commands[] = $command;
    }

    /**
     * Return flattened list of commands.
     *
     * @return \Generator
     */
    public function getCommands()
    {
        foreach ($this->commands as $command) {
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
        foreach ($this->getCommands() as $command) {
            dump($command);
        }

//        /**
//         * @var Driver[]           $drivers
//         * @var CommandInterface[] $commands
//         */
//        $drivers = $commands = [];
//
//        foreach ($this->getCommands() as $command) {
//            if ($command instanceof SQLCommandInterface) {
//                $driver = $command->getDriver();
//                if (!empty($driver) && !in_array($driver, $drivers)) {
//                    $drivers[] = $driver;
//                }
//            }
//
//            $commands[] = $command;
//        }
//
//        if (empty($commands)) {
//            return;
//        }
//
//        //Commands we executed and drivers with started transactions
//        $executedCommands = $wrappedDrivers = [];
//
//        try {
//            if ($forceTransaction || count($commands) > 1) {
//                //Starting transactions
//                foreach ($drivers as $driver) {
//                    $driver->beginTransaction();
//                    $wrappedDrivers[] = $driver;
//                }
//            }
//
//            //Run commands
//            foreach ($commands as $command) {
//                $command->execute();
//                $executedCommands[] = $command;
//            }
//        } catch (\Throwable $e) {
//            foreach (array_reverse($wrappedDrivers) as $driver) {
//                /** @var Driver $driver */
//                $driver->rollbackTransaction();
//            }
//
//            foreach (array_reverse($executedCommands) as $command) {
//                /** @var CommandInterface $command */
//                $command->rollBack();
//            }
//
//            $this->commands = [];
//            throw $e;
//        }
//
//        foreach (array_reverse($wrappedDrivers) as $driver) {
//            /** @var Driver $driver */
//            $driver->commitTransaction();
//        }
//
//        foreach ($executedCommands as $command) {
//            //This is the point when record will get related PK and FKs filled
//            $command->complete();
//        }
//
//        //Clean transaction
//        if ($clean) {
//            $this->commands = [];
//        }
    }
}