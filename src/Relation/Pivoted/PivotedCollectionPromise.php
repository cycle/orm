<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Relation\Pivoted;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Spiral\Cycle\Promise\Collection\CollectionPromiseInterface;
use Spiral\Cycle\Promise\PromiseInterface;

/**
 * Collection at top of pivoted (entity + context entity) promise.
 */
class PivotedCollectionPromise extends AbstractLazyCollection implements
    CollectionPromiseInterface,
    PivotedCollectionInterface
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
    public function setPivot($element, $pivot)
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
     * @inheritdoc
     */
    protected function doInitialize()
    {
        $storage = $this->promise->__resolve();
        $this->collection = new PivotedCollection($storage->getElements(), $storage->getContext());
    }
}