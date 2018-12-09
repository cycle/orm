<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Collection;

use Doctrine\Common\Collections\Collection;
use Spiral\ORM\PromiseInterface;

/**
 * Indicates that collection has been build at top of promise.
 */
interface PromisedInterface extends Collection
{
    /**
     * Promise associated with the collection.
     *
     * @return PromiseInterface
     */
    public function toPromise(): PromiseInterface;
}