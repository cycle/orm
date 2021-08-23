<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Closure;
use Cycle\ORM\Select\QueryBuilder;
use Cycle\Database\Query\SelectQuery;

/**
 * Provides the ability to configure relation specific where conditions.
 */
trait WhereTrait
{
    /**
     * @param string        $table  Table name to be automatically inserted into where conditions at place of {@}.
     * @param string        $target Query target section (accepts: where, having, onWhere, on)
     * @param array|Closure $where  Where conditions in a form or short array form.
     */
    private function setWhere(SelectQuery $query, string $table, string $target, $where = null): SelectQuery
    {
        if (empty($where)) {
            // no conditions, nothing to do
            return $query;
        }

        $proxy = new QueryBuilder($query, $this);
        $proxy = $proxy->withForward($target);

        return $proxy->where($where)->getQuery();
    }
}
