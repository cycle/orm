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
     * Configure query and loader pair using proxy strategy.
     *
     * @param QueryProxy $proxy
     */
    public function apply(QueryProxy $proxy);
}