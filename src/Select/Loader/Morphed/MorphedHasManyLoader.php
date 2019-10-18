<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader\Morphed;

use Cycle\ORM\Relation;
use Cycle\ORM\Select\Loader\HasManyLoader;
use Cycle\ORM\Select\Traits\WhereTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
class MorphedHasManyLoader extends HasManyLoader
{
    use WhereTrait;

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        return $this->setWhere(
            parent::configureQuery($query, $outerKeys),
            $this->getAlias(),
            $this->isJoined() ? 'onWhere' : 'where',
            [$this->localKey(Relation::MORPH_KEY) => $this->parent->getTarget()]
        );
    }
}
