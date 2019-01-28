<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select\Traits;

use Spiral\Cycle\Select\QueryBuilder;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to configure relation specific where conditions.
 */
trait WhereTrait
{
    /**
     * @param SelectQuery   $query
     * @param string        $table  Table name to be automatically inserted into where conditions at place of {@}.
     * @param string        $target Query target section (accepts: where, having, onWhere, on)
     * @param array|Closure $where  Where conditions in a form or short array form.
     * @return SelectQuery
     */
    private function setWhere(SelectQuery $query, string $table, string $target, $where = null): SelectQuery
    {
        if (empty($where)) {
            //No conditions, nothing to do
            return $query;
        }

        $proxy = new QueryBuilder($this->orm, $query, $this);
        $proxy->setForwarding($target)->where($where);

        return $proxy->getQuery();
    }
}