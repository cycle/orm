<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise\Collection;

use Doctrine\Common\Collections\Collection;
use Cycle\ORM\Promise\PromiseInterface;

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
    public function getPromise(): PromiseInterface;

    /**
     * Is the lazy collection already initialized?
     *
     * @return bool
     */
    public function isInitialized();
}