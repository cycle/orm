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
        if (!$this->isExecuted()) {
            throw new CommandException('The command was not run.');
        }
        return $this->affectedRows;
    }

    public function setScope(string $key, mixed $value): void
    {
        $this->scope[$key] = $value;
        // Indicate that context value is not required anymore.
        unset($this->waitScope[$key]);
    }
}
