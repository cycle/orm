<?php

declare(strict_types=1);

namespace Cycle\ORM\Promise\Collection;

use Cycle\ORM\Promise\PromiseInterface;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

/**
 * LazyLoading collection build at top of data promise.
 */
class CollectionPromise extends AbstractLazyCollection implements CollectionPromiseInterface, Selectable
{
    protected PromiseInterface $promise;

    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    public function getPromise(): PromiseInterface
    {
        return $this->promise;
    }

    public function matching(Criteria $criteria)
    {
        $this->initialize();

        return $this->collection->matching($criteria);
    }

    protected function doInitialize(): void
    {
        $this->collection = new ArrayCollection($this->promise->__resolve());
    }
}
