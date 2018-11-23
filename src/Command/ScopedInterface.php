<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

interface ScopedInterface extends CommandInterface
{
    /**
     * Wait for the scope value.
     *
     * @param string $key
     * @param bool   $required
     */
    public function waitScope(string $key, bool $required = true);

    /**
     * Indicate that scope value is not required anymore.
     *
     * @param string $key
     */
    public function freeScope(string $key);

    /**
     * @param string $key
     * @param mixed  $value
     */
    public function setScope(string $key, $value);

    /**
     * @return array
     */
    public function getScope(): array;
}