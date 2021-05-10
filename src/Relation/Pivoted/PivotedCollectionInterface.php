<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Doctrine\Common\Collections\Collection;
use SplObjectStorage;

/**
 * Carries pivot data associated with each element.
 */
interface PivotedCollectionInterface extends Collection
{
    /**
     * Return true if element has pivot data associated (can be null).
     */
    public function hasPivot(object $element): bool;

    /**
     * Return pivot data associated with element or null.
     *
     * @return mixed|null
     */
    public function getPivot(object $element);

    /**
     * Associate pivot data with the element.
     *
     * @param mixed  $pivot
     */
    public function setPivot(object $element, $pivot): void;

    /**
     * Get all associated pivot data.
     */
    public function getPivotContext(): SplObjectStorage;
}
