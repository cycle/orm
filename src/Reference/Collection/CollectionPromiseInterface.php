<?php

declare(strict_types=1);

namespace Cycle\ORM\Reference\Collection;

use Cycle\ORM\Reference\PromiseInterface;
use Doctrine\Common\Collections\Collection;

/**
 * Indicates that collection has been build at top of promise.
 */
interface CollectionPromiseInterface extends Collection
{
    /**
     * Promise associated with the collection.
     */
    public function getPromise(): PromiseInterface;

    /**
     * Is the lazy collection already initialized?
     *
     * @return bool
     */
    public function isInitialized();
}
