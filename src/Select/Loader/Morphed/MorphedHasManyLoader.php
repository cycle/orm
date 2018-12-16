<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select\Loader\Morphed;

use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select\Loader\HasManyLoader;
use Spiral\Cycle\Select\Traits\MorphedTrait;
use Spiral\Cycle\Select\Traits\WhereTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
class MorphedHasManyLoader extends HasManyLoader
{
    use WhereTrait, MorphedTrait;

    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        $parentAlias = $this->orm->getSchema()->define($this->parent->getTarget(), Schema::ALIAS);

        return $this->setWhere(
            parent::configureQuery($query, $outerKeys),
            $this->getAlias(),
            $this->isJoined() ? 'onWhere' : 'where',
            [
                $this->localKey(Relation::MORPH_KEY) => $parentAlias
            ]
        );
    }
}