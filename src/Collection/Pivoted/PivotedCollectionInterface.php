<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection\Pivoted;

/**
 * Carries pivot data associated with each element.
 *
 * @psalm-template TEntity of object
 * @psalm-template TPivot
 */
interface PivotedCollectionInterface
{
    /**
     * Return true if element has pivot data associated (can be null).
     *
     * @param TEntity $element
     */
    public function hasPivot(object $element): bool;

    /**
     * Return pivot data associated with element or null.
     *
     * @param TEntity $element
     *
     * @return TPivot
     */
    public function getPivot(object $element): mixed;

    /**
     * Associate pivot data with the element.
     *
     * @param TEntity $element
     * @param TPivot $pivot
     */
    public function setPivot(object $element, mixed $pivot): void;

    /**
     * Get all associated pivot data.
     */
    public function getPivotContext(): \SplObjectStorage;
}
