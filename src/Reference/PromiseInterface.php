<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference;

/**
 * Promises some related data.
 */
interface PromiseInterface extends ReferenceInterface
{
    /**
     * Return true if promise has been already resolved.
     */
    public function __loaded(): bool;

    /**
     * Resolve promise and return related data.
     *
     * @return mixed|null
     */
    public function __resolve();
}
