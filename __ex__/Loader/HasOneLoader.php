<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\ORM\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Database\Injections\Parameter;
use Spiral\ORM\Loader\Traits\WhereTrait;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\Nodes\SingularNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;

/**
 * Dedicated to load HAS_ONE relations, by default loader will prefer to join data into query.
 * Loader support MORPH_KEY.
 *
 * Please note that OUTER and INNER keys defined from perspective of parent (reversed for our
 * purposes).
 */
class HasOneLoader2 extends RelationLoader
{
    use WhereTrait;


    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if (!empty($this->options['using'])) {
            //Use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        if ($this->isJoined()) {
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                "{$this->getTable()} AS {$this->getAlias()}")
                ->on(
                    $this->localKey(Record::OUTER_KEY),
                    $this->parentKey(Record::INNER_KEY)
                );
        } else {
            //This relation is loaded using external query
            $query->where(
                $this->localKey(Record::OUTER_KEY),
                'IN',
                new Parameter($outerKeys)
            );
        }

        //Morphed records
        if (!empty($this->schema[Record::MORPH_KEY])) {
            $this->setWhere(
                $query,
                $this->getAlias(),
                $this->isJoined() ? 'onWhere' : 'where',
                [
                    $this->localKey(Record::MORPH_KEY) => $this->orm->define(
                        $this->parent->getClass(),
                        ORMInterface::R_ROLE_NAME
                    )
                ]
            );
        }

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        $node = new SingularNode(
            $this->schema[Record::RELATION_COLUMNS],
            $this->schema[Record::OUTER_KEY],
            $this->schema[Record::INNER_KEY],
            $this->schema[Record::SH_PRIMARY_KEY]
        );

        return $node->asJoined($this->isJoined());
    }
}