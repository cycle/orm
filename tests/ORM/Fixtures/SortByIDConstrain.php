<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\ConstrainInterface;
use Cycle\ORM\Select\QueryBuilder;

class SortByIDConstrain implements ConstrainInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->orderBy('id', 'ASC');
    }
}
