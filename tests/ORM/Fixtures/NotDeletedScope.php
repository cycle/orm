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

class NotDeletedScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->where('deleted_at', '=', null);
    }
}
