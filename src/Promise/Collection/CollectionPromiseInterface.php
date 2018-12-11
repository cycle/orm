<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Promise\Collection;

use Doctrine\Common\Collections\Collection;
use Spiral\Cycle\Promise\PromiseInterface;

/**
 * Indicates that collection has been build at top of promise.
 */
interface CollectionPromiseInterface extends Collection
{
    /**
     * Promise associated with the collection.
     *
     * @return PromiseInterface
     */
    public function toPromise(): PromiseInterface;

    /**
     * Is the lazy collection already initialized?
     *
     * @return bool
     */
    public function isInitialized();
}