<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
    /** @var PromiseInterface */
    protected $promise;

    /**
     * @param PromiseInterface $promise
     */
    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    /**
     * @inheritdoc
     */
    public function getPromise(): PromiseInterface
    {
        return $this->promise;
    }

    /**
     * @inheritDoc
     */
    public function matching(Criteria $criteria)
    {
        $this->initialize();

        return $this->collection->matching($criteria);
    }

    /**
     * @inheritdoc
     */
    protected function doInitialize(): void
    {
        $this->collection = new ArrayCollection($this->promise->__resolve());
    }
}
