<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Relation\Pivoted;

use Doctrine\Common\Collections\Collection;

/**
 * Carries pivot data associated with each element.
 */
interface PivotedCollectionInterface extends Collection
{
    /**
     * Return true if element has pivot data associated (can be null).
     *
     * @param object $element
     * @return bool
     */
    public function hasPivot($element): bool;

    /**
     * Return pivot data associated with element or null.
     *
     * @param object $element
     * @return mixed|null
     */
    public function getPivot($element);

    /**
     * Associate pivot data with the element.
     *
     * @param object $element
     * @param mixed  $pivot
     */
    public function setPivot($element, $pivot);

    /**
     * Get all associated pivot data.
     *
     * @return \SplObjectStorage
     */
    public function getPivotContext(): \SplObjectStorage;
}
