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
interface ContextualInterface extends CommandInterface
{
    /**
     * Wait for the context value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitContext(string $key, bool $required = true);

    /**
     * Indicate that context value is not required anymore.
     *
     * @param string $key
     */
    public function freeContext(string $key);

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
     * @param string $key
     * @param mixed  $value
     */
    public function setContext(string $key, $value);
}