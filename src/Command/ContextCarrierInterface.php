<?php

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\ORM\Context\ConsumerInterface;

/**
 * Command provide the ability to accept and carry the context to the persistence layer.
 * todo may be rename to StoreCommandInterface
 */
interface ContextCarrierInterface extends CommandInterface, ConsumerInterface
{
    /**
     * Wait for the context value. Command must not be ready until the value come if value is required.
     */
    public function waitContext(string $key, bool $required = true): void;

    /**
     * Get current command context.
     */
    public function getContext(): array;
}
