<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

interface StoreCommandInterface extends CommandInterface
{
    public function hasData(): bool;

    /**
     * @param mixed $value
     */
    public function registerAppendix(string $key, $value): void;
}
