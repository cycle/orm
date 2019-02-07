<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Select\ConstrainInterface;
use Spiral\Cycle\Select\QueryBuilder;

class SortByLevelDESCConstrain implements ConstrainInterface
{
    public function apply(QueryBuilder $query)
    {
        $query->orderBy('level', 'DESC');
    }
}