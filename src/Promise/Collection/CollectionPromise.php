<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Promise\Collection;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Spiral\Cycle\Promise\PromiseInterface;

/**
 * LazyLoading collection build at top of data promise.
 */
class CollectionPromise extends AbstractLazyCollection implements CollectionPromiseInterface
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
     * @inheritdoc
     */
    protected function doInitialize()
    {
        $this->collection = new ArrayCollection($this->promise->__resolve());
    }
}