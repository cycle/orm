<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Selector;

use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to modify the selector.
 */
interface ScopeInterface
{
    /**
     * @param SelectQuery $query
     * @return SelectQuery
     */
    public function apply(SelectQuery $query): SelectQuery;
}