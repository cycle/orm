<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Feature\Common\Relation\ManyToMany\Fixture;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\ORM\Select\ScopeInterface;

final class HiddenTagScope implements ScopeInterface
{
    public function apply(QueryBuilder $query): void
    {
        $query->where('name', '!=', 'hidden');
    }
}
