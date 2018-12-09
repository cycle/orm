<?php
/**
 * orm
 *
 * @author    Wolfy-J
 */

namespace Spiral\ORM\Loader\Traits;


use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Util\QueryWrapper;

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
            (new QueryWrapper($this->getAlias()))->withQuery($query)->orderBy($orderBy);
        }
    }

    /**
     * Joined table alias.
     *
     * @return string
     */
    abstract protected function getAlias(): string;
}