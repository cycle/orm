<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Context;

/**
 * Provides the ability to accept the forwarded key value.
 */
interface ConsumerInterface
{
    // Value destinations.
    public const DATA  = 1;
    public const SCOPE = 2;

    /**
     * Accept the value forwarded by another object. The `handled` value will always be false when forwarded using
     * initial trigger.
     *
     * @see ProducerInterface
     * @param string $key    Key name to accept the value.
     * @param mixed  $value  The key value.
     * @param bool   $update Indicates that value has not been handled by any other acceptor.
     * @param int    $stream One of the context types (data context, scope context).
     */
    public function register(string $key, $value, bool $update = false, int $stream = self::DATA);
}