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
use Spiral\ORM\Node\PivotedNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;

class ManyToManyLoader extends RelationLoader
{
    // todo: where trait
    // todo: constrain trait

    /**
     * When target role is null parent role to be used. Redefine this variable to revert behaviour
     * of ManyToMany relation.
     *
     * @see ManyToMorphedRelation
     * @var string|null
     */
    //  private $targetRole = null;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'method'     => self::POSTLOAD,
        'minify'     => true,
        'alias'      => null,
        'pivotAlias' => null,
        'using'      => null,
        'where'      => null,
        'wherePivot' => null,
        'orderBy'    => [],
        'limit'      => 0
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
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        return new PivotedNode(
            $this->schema[Relation::RELATION_COLUMNS],
            $this->schema[Relation::PIVOT_COLUMNS],
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::THOUGHT_INNER_KEY],
            $this->schema[Relation::THOUGHT_OUTER_KEY]
        );
    }
}