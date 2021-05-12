<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Cycle\ORM\Promise\Collection\CollectionPromiseInterface;
use Cycle\ORM\Promise\PromiseInterface;
use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use SplObjectStorage;

/**
 * Collection at top of pivoted (entity + context entity) promise.
 */
final class PivotedCollectionPromise extends AbstractLazyCollection implements
    CollectionPromiseInterface,
    PivotedCollectionInterface,
    Selectable
{
    protected PromiseInterface $promise;

    /** @var PivotedCollectionInterface */
    protected $collection;

    public function __construct(PivotedPromise $promise)
    {
        $this->promise = $promise;
    }

    public function getPromise(): PromiseInterface
    {
        return $this->promise;
    }

    public function hasPivot(object $element): bool
    {
        $this->initialize();
        return $this->collection->hasPivot($element);
    }

    public function getPivot(object $element)
    {
        $this->initialize();
        return $this->collection->getPivot($element);
    }

    public function setPivot(object $element, $pivot): void
    {
        $this->initialize();
        $this->collection->setPivot($element, $pivot);
    }

    public function getPivotContext(): SplObjectStorage
    {
        $this->initialize();
        return $this->collection->getPivotContext();
    }

    public function matching(Criteria $criteria): Collection
    {
        $this->initialize();
        return $this->collection->matching($criteria);
    }

    protected function doInitialize(): void
    {
        $storage = $this->promise->__resolve();
        $this->collection = new PivotedCollection($storage->getElements(), $storage->getContext());
    }
}
