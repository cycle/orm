<?php
declare(strict_types=1);/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */namespace Spiral\Cycle\Tests\Fixtures;

use Spiral\Cycle\Select\ConstrainInterface;
use Spiral\Cycle\Select\QueryBuilder;

class SortByMsgConstrain implements ConstrainInterface
{
    public function apply(QueryBuilder $query)
    {
        $query->orderBy('message', 'ASC');
    }
}