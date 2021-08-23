<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\Database\Query\SelectQuery;

/**
 * Declares an ability to modify parent query.
 */
interface JoinableInterface extends LoaderInterface
{
    /**
     * Indicates that relation is joined and must configure parent query.
     */
    public function isJoined(): bool;

    /**
     * Configure query with conditions, joins and columns.
     *
     * @param array $outerKeys Set of OUTER_KEY values collected by parent loader.
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery;
}
