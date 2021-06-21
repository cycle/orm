<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

interface StoreCommandInterface extends CommandInterface
{
    public function hasData(): bool;

    /**
     * Register optional value to store in database. Having this value would not cause command to be executed
     * if data is empty. In case the command is executed, the appendix data will be transferred to the entity state.
     *
     * @param mixed $value
     */
    public function registerAppendix(string $key, $value): void;

    /**
     * Add raw data to store.
     *
     * @param mixed $value
     */
    public function registerColumn(string $key, $value): void;
}
