<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Promise;

/**
 * Reference points to a remote entity.
 */
interface ReferenceInterface
{
    /**
     * Entity role associated with the promise.
     *
     * @return string
     */
    public function __role(): string;

    /**
     * Data to unique identify the entity. In most of cases simply contain outer key name (primary key) and
     * it's value.
     *
     * @return array
     */
    public function __scope(): array;
}
