<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Select;

/**
 * Provides the ability to modify the selector and/or entity loader. Can be used to implement multi-table inheritance.
 */
interface ConstrainInterface
{
    /**
     * Configure query and loader pair using proxy strategy.
     *
     * @param QueryBuilder $proxy
     */
    public function apply(QueryBuilder $proxy);
}