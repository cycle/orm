<?php

/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Spiral\Database\Query\SelectQuery;

/**
 * Declares an ability to modify parent query.
 */
interface JoinableInterface extends LoaderInterface
{
    /**
     * Indicates that relation is joined and must configure parent query.
     *
     * @return bool
     */
    public function isJoined(): bool;

    /**
     * Configure query with conditions, joins and columns.
     *
     * @param SelectQuery $query
     * @param array       $outerKeys Set of OUTER_KEY values collected by parent loader.
     * @return SelectQuery
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery;
}
