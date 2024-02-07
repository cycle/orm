<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap\Traits;

trait WaitFieldTrait
{
    protected array $waitingFields = [];

    /**
     * Wait for the field value.
     */
    public function waitField(string $key, bool $required = true): void
    {
        $this->waitingFields[$key] = ($required || ($this->waitingFields[$key] ?? false)) && !isset($this->data[$key]);
    }

    public function getWaitingFields(bool $requiredOnly = false): array
    {
        return \array_keys($requiredOnly ? \array_filter($this->waitingFields) : $this->waitingFields);
    }

    /**
     * Indicate that field value is not required anymore.
     */
    public function freeWaitingField(string $key): void
    {
        unset($this->waitingFields[$key]);
    }
}
