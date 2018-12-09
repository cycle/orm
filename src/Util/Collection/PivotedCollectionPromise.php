<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Util\Collection;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Spiral\ORM\PromiseInterface;
use Spiral\ORM\Util\PivotedPromise;

/**
 * Collection at top of pivoted (entity + context entity) promise.
 */
class PivotedCollectionPromise extends AbstractLazyCollection implements PivotedInterface, PromisedInterface
{
    /** @var PivotedPromise */
    protected $promise;

    /** @var PivotedInterface */
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
    public function toPromise(): PromiseInterface
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
        $storage = $this->promise->__resolveContext();
        $this->collection = new PivotedCollection($storage->getElements(), $storage->getContext());
    }
}