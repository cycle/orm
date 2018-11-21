<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Collection;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection with associated relation context. Attention, pivot context is lost when collection is partitioned or
 * filtered.
 */
class PivotedCollection extends ArrayCollection implements PivotedCollectionInterface
{
    /** @var \SplObjectStorage */
    private $pivotData;

    /**
     * @param array                  $elements
     * @param \SplObjectStorage|null $pivotData
     */
    public function __construct(array $elements = [], \SplObjectStorage $pivotData = null)
    {
        parent::__construct($elements);
        $this->pivotData = $pivotData ?? new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function hasPivot($element): bool
    {
        return $this->pivotData->offsetExists($element);
    }

    /**
     * @inheritdoc
     */
    public function getPivot($element)
    {
        return $this->pivotData[$element] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setPivot($element, $pivot)
    {
        $this->pivotData[$element] = $pivot;
    }

    /**
     * @inheritdoc
     */
    public function getPivotData(): \SplObjectStorage
    {
        return $this->pivotData;
    }
}