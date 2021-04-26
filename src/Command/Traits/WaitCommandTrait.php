<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Traits;

use Cycle\ORM\Command\CommandInterface;

/**
 * Provides ability to carry command.
 */
trait WaitCommandTrait
{
    /** @var CommandInterface[] */
    protected $waitCommand = [];

    public function waitCommand(CommandInterface $command): void
    {
        $this->waitCommand[] = $command;
    }

    public function isCommandsExecuted(): bool
    {
        foreach ($this->waitCommand as $command) {
            if (!$command->isExecuted()) {
                return false;
            }
        }
        return true;
    }
}
