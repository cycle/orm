<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection\Pivoted;

use Doctrine\Common\Collections\ArrayCollection;
use SplObjectStorage;

/**
 * Collection with associated relation context. Attention, pivot context is lost when collection is partitioned or
 * filtered.
 *
 * @template-extends  ArrayCollection
 */
class PivotedCollection extends ArrayCollection implements PivotedCollectionInterface
{
    protected SplObjectStorage $pivotContext;

    public function __construct(array $elements = [], SplObjectStorage $pivotData = null)
    {
        parent::__construct($elements);
        $this->pivotContext = $pivotData ?? new SplObjectStorage();
    }

    public function hasPivot(object $element): bool
    {
        return $this->pivotContext->offsetExists($element);
    }

    public function getPivot(object $element): mixed
    {
        return $this->pivotContext[$element] ?? null;
    }

    public function setPivot(object $element, mixed $pivot): void
    {
        $this->pivotContext[$element] = $pivot;
    }

    public function getPivotContext(): SplObjectStorage
    {
        return $this->pivotContext;
    }

    protected function createFrom(array $elements): static
    {
        $new = parent::createFrom($elements);
        $new->pivotContext = $this->pivotContext;

        return $new;
    }
}
