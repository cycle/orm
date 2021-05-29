<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

class_alias(
    ScopeTrait::class,
    __NAMESPACE__ . '\ConstrainTrait'
);


if (false) {
    /**
     * @deprecated Use {@see ScopeTrait} instead.
     */
    trait ConstrainTrait
    {
        use ScopeTrait;
    }
}
