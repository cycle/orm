<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection\Pivoted;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * Collection with associated relation context. Attention, pivot context is lost when collection is partitioned or
 * filtered.
 *
 * @psalm-template TKey of array-key
 * @psalm-template TEntity of object
 * @psalm-template TPivot of object
 *
 * @template-extends ArrayCollection<TKey, TEntity>
 *
 * @template-implements PivotedCollectionInterface<TEntity, TPivot>
 */
class PivotedCollection extends ArrayCollection implements PivotedCollectionInterface
{
    /** @var \SplObjectStorage<TEntity, TPivot> */
    protected \SplObjectStorage $pivotContext;

    /**
     * @param array<TKey, TEntity> $elements
     * @param \SplObjectStorage<TEntity, TPivot>|null $pivotData
     */
    final public function __construct(array $elements = [], ?\SplObjectStorage $pivotData = null)
    {
        parent::__construct($elements);
        $this->pivotContext = $pivotData ?? new \SplObjectStorage();
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

    public function getPivotContext(): \SplObjectStorage
    {
        return $this->pivotContext;
    }

    /**
     * @param array<K, V> $elements
     *
     * @return static<K, V, TPivot>
     *
     * @template K of TKey
     * @template V of TEntity
     */
    protected function createFrom(array $elements): static
    {
        /** @psalm-suppress MissingTemplateParam */
        $new = parent::createFrom($elements);
        $new->pivotContext = $this->pivotContext;

        return $new;
    }
}
