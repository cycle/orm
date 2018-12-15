<?php
/**
 * orm
 *
 * @author    Wolfy-J
 */

namespace Spiral\Cycle\Selector\Traits;

use Spiral\Cycle\Selector\QueryMapper;
use Spiral\Database\Query\SelectQuery;

/**
 * Provides ability to set LIMIT and ORDER BY in relation loaders.
 */
trait ConstrainTrait
{
    /**
     * @param SelectQuery $query
     * @param array       $orderBy
     */
    private function configureWindow(SelectQuery $query, array $orderBy)
    {
        if (!empty($orderBy)) {
            (new QueryMapper($this->getAlias()))->withQuery($query)->orderBy($orderBy);
        }
    }

    /**
     * Joined table alias.
     *
     * @return string
     */
    abstract protected function getAlias(): string;
}