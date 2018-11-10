<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Loader\Traits;

use Spiral\Database\Builders\SelectQuery;
use Spiral\ORM\Helpers\AliasDecorator;

/**
 * Provides ability to clarify Query where conditions in JOIN or WHERE statement, based on provided
 * values.
 */
trait WhereTrait
{
    /**
     * @param SelectQuery $query
     * @param string      $table Table name to be automatically inserted into where conditions at
     *                            place of {@}.
     * @param string      $target Query target section (accepts: where, having, onWhere, on)
     * @param array       $where Where conditions in a form or short array form.
     */
    private function setWhere(
        SelectQuery $query,
        string $table,
        string $target,
        array $where = null
    ) {
        if (empty($where)) {
            //No conditions, nothing to do
            return;
        }

        $decorator = new AliasDecorator($query, $target, $table);
        $decorator->where($where);
    }
}