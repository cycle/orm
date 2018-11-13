<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Loader\Relation;

use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Node\AbstractNode;
use Spiral\ORM\Node\SingularNode;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

/**
 * Load parent data. Similar to HasOne but use POSTLOAD as default method.
 */
class BelongsToLoader extends RelationLoader
{
    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'method' => self::POSTLOAD,
        'minify' => true,
        'alias'  => null,
        'using'  => null,
        'where'  => null,
    ];

    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if (!empty($this->options['using'])) {
            // use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        $localKey = $this->localKey(Relation::OUTER_KEY);

        if ($this->isJoined()) {
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                $this->getJoinedTable()
            )->on(
                $localKey,
                $this->parentKey(Relation::INNER_KEY)
            );
        } else {
            // relation is loaded using external query
            $query->where($localKey, 'IN', new Parameter($outerKeys));
        }

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        return new SingularNode(
            $this->getColumns(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::INNER_KEY]
        );
    }
}