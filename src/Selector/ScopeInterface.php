<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector;

/**
 * Provides the ability to modify the selector and entity loader. Can be used to implement multi-table inheritance.
 */
interface ScopeInterface
{
    /**
     * Configure query and loader with needed conditions, queries or additional relations.
     *
     * @param QueryProxy $query
     */
    // todo: we can create proxy inside
    public function apply(QueryProxy $query);
}