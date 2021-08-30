<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection\Pivoted;

use SplObjectStorage;

/**
 * Carries pivot data associated with each element.
 */
interface PivotedCollectionInterface
{
    /**
     * Return true if element has pivot data associated (can be null).
     */
    public function hasPivot(object $element): bool;

    /**
     * Return pivot data associated with element or null.
     */
    public function getPivot(object $element): mixed;

    /**
     * Associate pivot data with the element.
     */
    public function setPivot(object $element, mixed $pivot): void;

    /**
     * Get all associated pivot data.
     */
    public function getPivotContext(): SplObjectStorage;
}
