<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Event;

use Spiral\Models\EntityInterface;
use Spiral\Models\Events\EntityEvent;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;

class RecordEvent extends EntityEvent
{
    /** @var CommandInterface */
    private $command;

    /**
     * @param EntityInterface  $entity
     * @param CommandInterface $command
     */
    public function __construct(EntityInterface $entity, CommandInterface $command)
    {
        parent::__construct($entity);
        $this->command = $command;
    }

    /**
     * Indication that command is contextual (i.e. have mutable data).
     *
     * @return bool
     */
    public function isMutable(): bool
    {
        return $this->command instanceof CommandPromiseInterface;
    }

    /**
     * @return CommandInterface
     */
    public function getCommand(): CommandInterface
    {
        return $this->command;
    }
}