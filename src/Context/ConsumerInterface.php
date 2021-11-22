<?php

declare(strict_types=1);

namespace Cycle\ORM\Context;

/**
 * Provides the ability to accept the forwarded key value.
 *
 * @internal
 */
interface ConsumerInterface
{
    /**
     * Accept the value forwarded by another object.
     *
     * @param string $key    Key name to accept the value.
     * @param mixed  $value  The key value.
     */
    public function register(
        string $key,
        mixed $value
    ): void;
}
