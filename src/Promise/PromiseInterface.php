<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Promise;

/**
 * Promises some related data.
 */
interface PromiseInterface extends ReferenceInterface
{
    /**
     * Return true if promise has been already resolved.
     *
     * @return bool
     */
    public function __loaded(): bool;

    /**
     * Resolve promise and return related data.
     *
     * @return mixed|null
     */
    public function __resolve();
}
