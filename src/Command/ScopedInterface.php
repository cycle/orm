<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Command;

use Spiral\ORM\Context\AcceptorInterface;

/**
 * Command indicates the ability to accept the forwarded scope values.
 */
interface ScopedInterface extends CommandInterface, AcceptorInterface
{
    /**
     * Wait for the scope value. Command must not be ready until the value come.
     *
     * @param string $key
     */
    public function waitScope(string $key);

    /**
     * Set scope value.
     *
     * @param string $key
     * @param mixed  $value
     */
    public function setScope(string $key, $value);

    /**
     * @return array
     */
    public function getScope(): array;
}