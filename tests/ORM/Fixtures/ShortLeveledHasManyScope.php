<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

class ShortLeveledHasManyScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->where('level', '>=', 3)->orderBy('level', 'DESC');
    }
}
