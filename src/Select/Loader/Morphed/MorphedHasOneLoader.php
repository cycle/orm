<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader\Morphed;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Relation;
use Cycle\ORM\Select\Loader\HasOneLoader;
use Cycle\ORM\Select\Traits\WhereTrait;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
class MorphedHasOneLoader extends HasOneLoader
{
    use WhereTrait;

    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        return $this->setWhere(
            parent::configureQuery($query, $outerKeys),
            $this->isJoined() ? 'onWhere' : 'where',
            [$this->localKey(Relation::MORPH_KEY) => $this->parent->getTarget()],
        );
    }
}
