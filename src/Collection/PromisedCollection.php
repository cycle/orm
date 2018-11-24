<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

use Doctrine\Common\Collections\AbstractLazyCollection;
use Doctrine\Common\Collections\ArrayCollection;
use Spiral\ORM\PromiseInterface;

class PromisedCollection extends AbstractLazyCollection
{
    private $promise;

    public function __construct(PromiseInterface $promise)
    {
        $this->promise = $promise;
    }

    protected function doInitialize()
    {
        $this->collection = new ArrayCollection($this->promise->__resolve());
    }

    public function getPromise(): PromiseInterface
    {
        return $this->promise;
    }

    // todo: get promise?
}