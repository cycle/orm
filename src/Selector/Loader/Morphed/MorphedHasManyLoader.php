<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector\Loader\Morphed;

use Spiral\Database\Query\SelectQuery;
use Spiral\Cycle\Selector\Loader\HasManyLoader;
use Spiral\Cycle\Selector\Traits\WhereTrait;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
class MorphedHasManyLoader extends HasManyLoader
{
    use WhereTrait;

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