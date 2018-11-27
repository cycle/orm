<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

/**
 * Promises some related data.
 */
interface PromiseInterface
{
    /**
     * Return true if promise has been already resolved.
     *
     * @return bool
     */
    public function __loaded(): bool;

    /**
     * Return association data used to resolve the promise.
     * In most of cases simply contain outer key name and it's
     * value.
     *
     * @return array
     */
    public function __context(): array;

    /**
     * Resolve promise and return related data.
     *
     * @return mixed|null
     */
    public function __resolve();
}