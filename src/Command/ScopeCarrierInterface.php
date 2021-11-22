<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\ORM\Exception\CommandException;

/**
 * Command indicates the ability to accept the forwarded scope values.
 */
interface ScopeCarrierInterface extends CommandInterface
{
    /**
     * Wait for the scope value. Command must not be ready until the value come.
     */
    public function waitScope(string ...$keys): void;

    /**
     * Set scope value. Passed key also should be removed from wait-list.
     */
    public function setScope(string $key, mixed $value): void;

    public function getScope(): array;

    /**
     * Get count of affected rows after execution
     *
     * @throws CommandException thrown when the method is called before the command is executed
     */
    public function getAffectedRows(): int;
}
