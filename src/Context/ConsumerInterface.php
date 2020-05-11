<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Context;

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
     * @param string $key    Key name to accept the value.
     * @param mixed  $value  The key value.
     * @param bool   $fresh  Indicates that value has not been received by any other acceptor.
     * @param int    $stream One of the context types (data context, scope context).
     * @see ProducerInterface
     */
    public function register(
        string $key,
        $value,
        bool $fresh = false,
        int $stream = self::DATA
    );
}
