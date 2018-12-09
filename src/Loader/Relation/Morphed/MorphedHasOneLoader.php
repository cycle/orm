<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Loader\Relation\Morphed;

use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Loader\Relation\HasOneLoader;
use Spiral\ORM\Loader\Traits\WhereTrait;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
class MorphedHasOneLoader extends HasOneLoader
{
    use WhereTrait;

    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        $parentAlias = $this->orm->getSchema()->define($this->parent->getRole(), Schema::ALIAS);

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