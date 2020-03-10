<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Cycle\ORM\Promise\Collection\CollectionPromiseInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;

/**
 * Collection at top of pivoted (entity + context entity) promise.
 */
final class PivotedCollectionPromise extends AbstractLazyCollection implements
    CollectionPromiseInterface,
    PivotedCollectionInterface,
    Selectable
{
    /** @var PivotedPromise */
    protected $promise;

    /** @var PivotedCollectionInterface */
    protected $collection;

    /**
     * @param PivotedPromise $promise
     */
    public function __construct(PivotedPromise $promise)
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
     * @inheritdoc
     */
    public function hasPivot($element): bool
    {
        $this->initialize();
        return $this->collection->hasPivot($element);
    }

    /**
     * @inheritdoc
     */
    public function getPivot($element)
    {
        $this->initialize();
        return $this->collection->getPivot($element);
    }

    /**
     * @inheritdoc
     */
    public function setPivot($element, $pivot): void
    {
        $this->initialize();
        $this->collection->setPivot($element, $pivot);
    }

    /**
     * @inheritdoc
     */
    public function getPivotContext(): \SplObjectStorage
    {
        $this->initialize();
        return $this->collection->getPivotContext();
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
        $storage = $this->promise->__resolve();
        $this->collection = new PivotedCollection($storage->getElements(), $storage->getContext());
    }
}
