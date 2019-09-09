<?php


namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;

class LeveledHasManyConstrain implements ConstrainInterface
{
    public function apply(QueryBuilder $query)
    {
        $query->where('@.level', '>=', 3)->orderBy('@.level', 'DESC');
    }
}
