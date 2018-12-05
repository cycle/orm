<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Context;

/**
 * Provides the ability to forward key value from one place to another.
 */
interface ForwarderInterface
{
    /**
     * Links key from forwarder to the acceptor and forwards key changes when they occur.
     *
     * @see AcceptorInterface
     * @param string            $key      The key name to forward.
     * @param AcceptorInterface $acceptor Class to accept the key.
     * @param string            $target   Target key name in acceptor.
     * @param bool              $trigger  When set to true forwarder is allowed to send key immediately.
     * @param int               $type     One of the context types (data context, scope context).
     */
    public function forward(
        string $key,
        AcceptorInterface $acceptor,
        string $target,
        bool $trigger = false,
        int $type = AcceptorInterface::DATA
    );
}