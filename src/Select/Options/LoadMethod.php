<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select;

enum LoadMethod: int
{
    /** Load relation data within the same query using JOIN */
    case SingleQuery = Select::SINGLE_QUERY;

    /** Load related data using a separate query */
    case OuterQuery = Select::OUTER_QUERY;
}
