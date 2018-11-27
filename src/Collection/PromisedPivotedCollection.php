<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;


use Doctrine\Common\Collections\AbstractLazyCollection;
use Spiral\ORM\Util\PivotedPromise;
use Spiral\ORM\PromiseInterface;

class PromisedPivotedCollection extends AbstractLazyCollection implements PivotedCollectionInterface
{
    private $promise;

    public function __construct(PivotedPromise $promise)
    {
        $this->promise = $promise;
    }

    protected function doInitialize()
    {
        $storage = $this->promise->__resolveContext();
        $this->collection = new PivotedCollection($storage->getElements(), $storage->getContext());
    }

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
    public function getPivotData(): \SplObjectStorage
    {
        $this->initialize();
        return $this->collection->getPivotData();
    }
}