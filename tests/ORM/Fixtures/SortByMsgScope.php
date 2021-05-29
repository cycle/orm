<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

class SortByMsgScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->orderBy('message', 'ASC');
    }
}
