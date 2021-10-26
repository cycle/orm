<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Select\QueryBuilder;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to configure relation specific where conditions.
 */
trait OrderByTrait
{
    /**
     * @param SelectQuery   $query
     * @param string        $table  Table name to be automatically inserted into where conditions at place of {@}.
     * @param array         $order  Associative array where the keys are field names
     *                              and the values are ASC or DESC strings
     *
     * @return SelectQuery
     */
    private function setOrderBy(SelectQuery $query, string $table, array $order = []): SelectQuery
    {
        if ($order === []) {
            return $query;
        }

        $proxy = new QueryBuilder($query, $this);

        return $proxy->orderBy($order)->getQuery();
    }
}
