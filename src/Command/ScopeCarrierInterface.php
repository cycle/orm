<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Command;

use Spiral\Cycle\Context\ConsumerInterface;

/**
 * Command indicates the ability to accept the forwarded scope values.
 */
interface ScopeCarrierInterface extends CommandInterface, ConsumerInterface
{
    /**
     * Wait for the scope value. Command must not be ready until the value come.
     *
     * @param string $key
     */
    public function waitScope(string $key);

    /**
     * @return array
     */
    public function getScope(): array;
}