<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Command;

use Cycle\ORM\Context\ConsumerInterface;

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
