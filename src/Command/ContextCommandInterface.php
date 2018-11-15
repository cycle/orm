<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Command;

/**
 * Promise commands used to carry FK and PK values across commands pipeline, other commands are
 * able to mount it's values into parent context or read from it.
 */
interface ContextCommandInterface extends CommandInterface
{
    /**
     * Get current command context.
     *
     * @return array
     */
    public function getContext(): array;

    /**
     * Add context value, usually FK. Must be set before command being executed, usually in leading
     * command "execute" event.
     *
     * @param string $name
     * @param mixed  $value
     */
    public function setContext(string $name, $value);
}