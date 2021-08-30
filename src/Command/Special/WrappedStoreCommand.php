<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Special;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\StoreCommandInterface;

final class WrappedStoreCommand extends WrappedCommand implements StoreCommandInterface
{
    /** @var StoreCommandInterface */
    protected CommandInterface $command;

    public static function wrapStoreCommand(StoreCommandInterface $command): self
    {
        return new self($command);
    }

    public function registerAppendix(string $key, mixed $value): void
    {
        $this->command->registerAppendix($key, $value);
    }

    public function registerColumn(string $key, mixed $value): void
    {
        $this->command->registerColumn($key, $value);
    }

    public function hasData(): bool
    {
        return $this->command->hasData();
    }
}
