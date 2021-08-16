<?php

declare(strict_types=1);

namespace Cycle\ORM\Command\Traits;

use Cycle\ORM\Exception\CommandException;

trait ScopeTrait
{
    protected array $scope = [];

    /** @var string[] */
    protected array $waitScope = [];

    private int $affectedRows;

    /**
     * Wait for the context value.
     */
    public function waitScope(string ...$keys): void
    {
        foreach ($keys as $key) {
            $this->waitScope[$key] = true;
        }
    }

    public function isScopeReady(): bool
    {
        return $this->waitScope === [];
    }

    public function getScope(): array
    {
        return $this->scope;
    }

    public function getAffectedRows(): int
    {
        if (!$this->executed) {
            throw new CommandException('The command was not run.');
        }
        return $this->affectedRows;
    }

    /**
     * Set scope value.
     *
     * @param mixed  $value
     */
    public function setScope(string $key, $value): void
    {
        $this->scope[$key] = $value;
    }

    /**
     * Indicate that context value is not required anymore.
     */
    protected function freeScope(string $key): void
    {
        unset($this->waitScope[$key]);
    }
}
