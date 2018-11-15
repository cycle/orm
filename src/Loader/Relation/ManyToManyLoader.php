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
use Spiral\ORM\Node\PivotedNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Relation;
use Spiral\ORM\Schema;

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
            //Use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        if ($this->isJoined()) {
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                $this->pivotTable() . ' AS ' . $this->pivotAlias())
                ->on(
                    $this->pivotKey(Relation::THOUGHT_INNER_KEY),
                    $this->parentKey(Relation::INNER_KEY)
                );
        } else {
            $query->innerJoin(
                $this->pivotTable() . ' AS ' . $this->pivotAlias())
                ->on(
                    $this->pivotKey(Relation::THOUGHT_OUTER_KEY),
                    $this->localKey(Relation::OUTER_KEY)
                )->where(
                    $this->pivotKey(Relation::THOUGHT_INNER_KEY),
                    new Parameter($outerKeys)
                );

            //   $this->configureWindow($query, $this->options['orderBy'], $this->options['limit']);
        }

        //When relation is joined we will use ON statements, when not - normal WHERE
        $whereTarget = $this->isJoined() ? 'onWhere' : 'where';

        //Pivot conditions specified in relation schema
        //        $this->setWhere(
        //            $query,
        //            $this->pivotAlias(),
        //            $whereTarget,
        //            $this->schema[Relation::WHERE_PIVOT]
        //        );

        //Pivot conditions specified by user
        // $this->setWhere($query, $this->pivotAlias(), $whereTarget, $this->options['wherePivot']);

        if ($this->isJoined()) {
            //Actual data is always INNER join
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                $this->getJoinedTable()
            )->on(
                $this->localKey(Relation::OUTER_KEY),
                $this->pivotKey(Relation::THOUGHT_OUTER_KEY)
            );
        }

        //Where conditions specified in relation definition
        //  $this->setWhere($query, $this->getAlias(), $whereTarget, $this->schema[Relation::WHERE]);

        //User specified WHERE conditions
        // $this->setWhere($query, $this->getAlias(), $whereTarget, $this->options['where']);

        return parent::configureQuery($query);
    }

    /**
     * @inheritdoc
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ): SelectQuery {
        //Pivot table source alias
        $alias = $this->pivotAlias();

        $columns = $overwrite ? [] : $query->getColumns();
        foreach ($this->pivotColumns() as $name) {
            $column = $name;

            if ($minify) {
                //Let's use column number instead of full name
                $column = 'p_c' . count($columns);
            }

            $columns[] = "{$alias}.{$name} AS {$prefix}{$column}";
        }

        //Updating column set
        $query->columns($columns);

        return parent::mountColumns($query, $minify, $prefix, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        return new PivotedNode(
            $this->define(Schema::COLUMNS),
            $this->schema[Relation::PIVOT_COLUMNS],
            $this->schema[Relation::OUTER_KEY],
            $this->schema[Relation::THOUGHT_INNER_KEY],
            $this->schema[Relation::THOUGHT_OUTER_KEY]
        );
    }

    /**
     * Pivot table name.
     *
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->schema[Relation::PIVOT_TABLE];
    }

    /**
     * Pivot table alias, depends on relation table alias.
     *
     * @return string
     */
    protected function pivotAlias(): string
    {
        if (!empty($this->options['pivotAlias'])) {
            return $this->options['pivotAlias'];
        }

        return $this->getAlias() . '_pivot';
    }

    /**
     * @return array
     */
    protected function pivotColumns(): array
    {
        return $this->schema[Relation::PIVOT_COLUMNS];
    }

    /**
     * Key related to pivot table. Must include pivot table alias.
     *
     * @see pivotKey()
     *
     * @param string $key
     *
     * @return null|string
     */
    protected function pivotKey(string $key)
    {
        if (!isset($this->schema[$key])) {
            return null;
        }

        return $this->pivotAlias() . '.' . $this->schema[$key];
    }

}