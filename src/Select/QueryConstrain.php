<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use function class_alias;

class_alias(QueryScope::class, __NAMESPACE__ . '\QueryConstrain');

if (false) {
    /**
     * @deprecated Use {@see QueryScope} instead.
     */
    class QueryConstrain extends QueryScope
    {
    }
}
