<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;

class LeveledHasManyConstrain implements ConstrainInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->where('@.level', '>=', 3)->orderBy('@.level', 'DESC');
    }
}
