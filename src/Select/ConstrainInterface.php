<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Select;

/**
 * Provides the ability to modify the selector and/or entity loader. Can be used to implement multi-table inheritance.
 */
interface ConstrainInterface
{
    /**
     * Configure query and loader pair using proxy strategy.
     *
     * @param QueryBuilder $query
     */
    public function apply(QueryBuilder $query);
}