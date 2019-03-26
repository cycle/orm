<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Context;

/**
 * Provides the ability to forward key value from one place to another.
 */
interface ProducerInterface
{
    /**
     * Links key from forwarder to the acceptor and forwards key changes when they occur.
     *
     * @see ConsumerInterface
     * @param string            $key      The key name to forward.
     * @param ConsumerInterface $consumer Class to accept the key.
     * @param string            $target   Target key name in acceptor.
     * @param bool              $trigger  When set to true forwarder is allowed to send key immediately.
     * @param int               $stream   One of the context types (data context, scope context).
     */
    public function forward(
        string $key,
        ConsumerInterface $consumer,
        string $target,
        bool $trigger = false,
        int $stream = ConsumerInterface::DATA
    );
}