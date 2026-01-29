<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Traits;

use Cycle\ORM\Select\QueryBuilder;
use Cycle\Database\Query\SelectQuery;

/**
 * Provides the ability to configure relation specific where conditions.
 *
 * @internal
 */
trait WhereTrait
{
    /**
     * @param string        $target Query target section (accepts: where, having, onWhere, on)
     * @param array|\Closure $where  Where conditions in a form or short array form.
     */
    private function setWhere(
        SelectQuery $query,
        string $target,
        array|\Closure|null $where = null,
    ): SelectQuery {
        if ($where === []) {
            // no conditions, nothing to do
            return $query;
        }

        $proxy = new QueryBuilder($query, $this);
        $proxy = $proxy->withForward($target);

        return $proxy->where($where)->getQuery();
    }
}
