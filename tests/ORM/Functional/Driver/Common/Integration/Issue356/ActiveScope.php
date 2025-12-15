<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue356;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

class ActiveScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->where('active', true);
    }
}
