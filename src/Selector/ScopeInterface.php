<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector;

use Spiral\Database\Query\SelectQuery;

/**
 * Provides the ability to modify the selector and entity loader. Can be used to implement multi-table inheritance.
 */
interface ScopeInterface
{
    /**
     * Configure query and loader with needed conditions, queries or additional relations.
     *
     * @param SelectQuery    $query  Mutable version of the query.
     * @param AbstractLoader $loader Mutable version of the entity loader.
     */
    public function apply(SelectQuery $query, AbstractLoader $loader);
}