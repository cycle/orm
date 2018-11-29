<?php
/**
 * orm
 *
 * @author    Wolfy-J
 */

namespace Spiral\ORM\Loader\Traits;


use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Util\AliasDecorator;

/**
 * Provides ability to set LIMIT and ORDER BY in relation loaders.
 */
trait ConstrainTrait
{
    /**
     * @param SelectQuery $query
     * @param array       $orderBy
     * @param int         $limit 0 when no selection.
     */
    private function configureWindow(SelectQuery $query, array $orderBy, int $limit = 0)
    {
        if (!empty($orderBy)) {
            $decorator = new AliasDecorator($query, 'where', $this->getAlias());
            $decorator->orderBy($orderBy);
        }

        if ($limit !== 0) {
            $query->limit($limit);
        }
    }

    /**
     * Joined table alias.
     *
     * @return string
     */
    abstract protected function getAlias(): string;
}