<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection with associated relation context. Attention, pivot context is lost when collection is partitioned or
 * filtered.
 */
final class PivotedCollection extends ArrayCollection implements PivotedCollectionInterface
{
    /** @var \SplObjectStorage */
    protected $pivotContext;

    /**
     * @param array                  $elements
     * @param \SplObjectStorage|null $pivotData
     */
    public function __construct(array $elements = [], \SplObjectStorage $pivotData = null)
    {
        parent::__construct($elements);
        $this->pivotContext = $pivotData ?? new \SplObjectStorage();
    }

    /**
     * @inheritdoc
     */
    public function hasPivot($element): bool
    {
        return $this->pivotContext->offsetExists($element);
    }

    /**
     * @inheritdoc
     */
    public function getPivot($element)
    {
        return $this->pivotContext[$element] ?? null;
    }

    /**
     * @inheritdoc
     */
    public function setPivot($element, $pivot): void
    {
        $this->pivotContext[$element] = $pivot;
    }

    /**
     * @inheritdoc
     */
    public function getPivotContext(): \SplObjectStorage
    {
        return $this->pivotContext;
    }
}
