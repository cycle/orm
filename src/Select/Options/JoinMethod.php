<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Options;

use Cycle\ORM\Select\JoinableLoader;

enum JoinMethod: int
{
    /** INNER JOIN */
    case InnerJoin = JoinableLoader::JOIN;

    /** LEFT JOIN */
    case LeftJoin = JoinableLoader::LEFT_JOIN;
}
