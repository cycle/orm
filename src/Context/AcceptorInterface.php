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
interface AcceptorInterface
{
    // Value destinations.
    public const DATA  = 1;
    public const SCOPE = 2;

    /**
     * Accept the value forwarded by another object. The `handled` value will always be false when forwarded using
     * initial trigger.
     *
     * @see ForwarderInterface
     * @param string $key     Key name to accept the value.
     * @param string $value   The key value.
     * @param bool   $handled Indicates that value has not been handled by any other acceptor.
     * @param int    $type    One of the context types (data context, scope context).
     */
    public function accept(string $key, ?string $value, bool $handled = false, int $type = self::DATA);
}