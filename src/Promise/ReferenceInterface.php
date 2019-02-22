<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Promise;

interface ReferenceInterface
{
    /**
     * Entity role associated with the promise.
     *
     * @return string
     */
    public function __role(): string;

    /**
     * Return association data used to resolve the promise.
     * In most of cases simply contain outer key name and it's
     * value.
     *
     * @return array
     */
    public function __scope(): array;
}