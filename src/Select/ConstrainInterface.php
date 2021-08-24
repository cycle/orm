<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

\class_alias(ScopeInterface::class, __NAMESPACE__ . '\ConstrainInterface');

if (false) {
    /**
     * @deprecated Use {@see ScopeInterface} instead.
     */
    interface ConstrainInterface extends ScopeInterface
    {
    }
}
