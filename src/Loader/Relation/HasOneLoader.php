<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Loader\Relation;

use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Loader\RelationLoader;
use Spiral\ORM\Node\AbstractNode;
use Spiral\ORM\Node\SingularNode;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

class HasOneLoader extends RelationLoader
{
    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'method' => self::INLOAD,
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

        if ($this->isJoined()) {
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                "{$this->define(Schema::TABLE)} AS {$this->getAlias()}"
            )->on(
                $this->localKey(Relation::OUTER_KEY),
                $this->parentKey(Relation::INNER_KEY)
            );
        }
//        else {
//            //This relation is loaded using external query
//            $query->where(
//                $this->localKey(Record::OUTER_KEY),
//                'IN',
//                new Parameter($outerKeys)
//            );
//        }

        //Morphed records
//        if (!empty($this->schema[Record::MORPH_KEY])) {
//            $this->setWhere(
//                $query,
//                $this->getAlias(),
//                $this->isJoined() ? 'onWhere' : 'where',
//                [
//                    $this->localKey(Record::MORPH_KEY) => $this->orm->define(
//                        $this->parent->getClass(),
//                        ORMInterface::R_ROLE_NAME
//                    )
//                ]
//            );
//        }

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