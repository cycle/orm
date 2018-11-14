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
use Spiral\ORM\Node\ArrayNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

class HasManyLoader extends RelationLoader
{
    // todo: where trait
    // todo: constrain trait

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'method'  => self::INLOAD,
        'minify'  => true,
        'alias'   => null,
        'using'   => null,
        'where'   => null,
        'orderBy' => [],
        'limit'   => 0
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(ORMInterface $orm, string $class, string $relation, array $schema)
    {
        parent::__construct($orm, $class, $relation, $schema);
        $this->options['orderBy'] = $schema[Relation::ORDER_BY] ?? [];
    }

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

            // todo: configureWindow
        }

        // todo: where configuration

        //todo: Morphed records
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
        return new ArrayNode(
            $this->getColumns(),
            $this->define(Schema::PRIMARY_KEY),
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::INNER_KEY]
        );
    }
}