<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Database\Injections\Parameter;
use Spiral\Treap\Loader\Traits\ConstrainTrait;
use Spiral\Treap\Loader\Traits\WhereTrait;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\Nodes\PivotedNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;
use Spiral\ORM\RecordEntity;

/**
 * ManyToMany loader will not only load related data, but will include pivot table data into record
 * property "@pivot". Loader support WHERE conditions for both related data and pivot table.
 *
 * It's STRONGLY recommended to load many-to-many data using postload method. However relation still
 * can be used to filter query.
 */
class ManyToManyLoader extends RelationLoader
{
    use WhereTrait, ConstrainTrait;

    /**
     * When target role is null parent role to be used. Redefine this variable to revert behaviour
     * of ManyToMany relation.
     *
     * @see ManyToMorphedRelation
     * @var string|null
     */
    private $targetRole = null;

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
     * @param string                   $class
     * @param string                   $relation
     * @param array                    $schema
     * @param \Spiral\ORM\ORMInterface $orm
     * @param string|null              $targetRole
     */
    public function __construct(
        $class,
        $relation,
        array $schema,
        ORMInterface $orm,
        string $targetRole = null
    ) {
        parent::__construct($class, $relation, $schema, $orm);
        $this->targetRole = $targetRole;

        if (!empty($schema[RecordEntity::ORDER_BY])) {
            //Default sorting direction
            $this->options['orderBy'] = $schema[RecordEntity::ORDER_BY];
        }
    }

    /**
     * {@inheritdoc}
     *
     * Visibility up.
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
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
                    $this->pivotKey(Record::THOUGHT_INNER_KEY),
                    $this->parentKey(Record::INNER_KEY)
                );
        } else {
            $query->innerJoin(
                $this->pivotTable() . ' AS ' . $this->pivotAlias())
                ->on(
                    $this->pivotKey(Record::THOUGHT_OUTER_KEY),
                    $this->localKey(Record::OUTER_KEY)
                )->where(
                    $this->pivotKey(Record::THOUGHT_INNER_KEY),
                    new Parameter($outerKeys)
                );

            $this->configureWindow($query, $this->options['orderBy'], $this->options['limit']);
        }

        //When relation is joined we will use ON statements, when not - normal WHERE
        $whereTarget = $this->isJoined() ? 'onWhere' : 'where';

        //Pivot conditions specified in relation schema
        $this->setWhere(
            $query,
            $this->pivotAlias(),
            $whereTarget,
            $this->schema[Record::WHERE_PIVOT]
        );

        //Additional morphed conditions
        if (!empty($this->schema[Record::MORPH_KEY])) {
            $this->setWhere(
                $query,
                $this->pivotAlias(),
                'onWhere',
                [$this->pivotKey(Record::MORPH_KEY) => $this->targetRole()]
            );
        }

        //Pivot conditions specified by user
        $this->setWhere($query, $this->pivotAlias(), $whereTarget, $this->options['wherePivot']);

        if ($this->isJoined()) {
            //Actual data is always INNER join
            $query->join(
                $this->getMethod() == self::JOIN ? 'INNER' : 'LEFT',
                $this->getTable() . ' AS ' . $this->getAlias()
            )->on(
                $this->localKey(Record::OUTER_KEY),
                $this->pivotKey(Record::THOUGHT_OUTER_KEY)
            );
        }

        //Where conditions specified in relation definition
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->schema[Record::WHERE]);

        //User specified WHERE conditions
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->options['where']);

        return parent::configureQuery($query);
    }

    /**
     * Set columns into SelectQuery.
     *
     * @param SelectQuery $query
     * @param bool        $minify    Minify column names (will work in case when query parsed in
     *                               FETCH_NUM mode).
     * @param string      $prefix    Prefix to be added for each column name.
     * @param bool        $overwrite When set to true existed columns will be removed.
     */
    protected function mountColumns(
        SelectQuery $query,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false
    ) {
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

        parent::mountColumns($query, $minify, $prefix, false);
    }

    /**
     * {@inheritdoc}
     */
    protected function initNode(): AbstractNode
    {
        $node = new PivotedNode(
            $this->schema[Record::RELATION_COLUMNS],
            $this->schema[Record::PIVOT_COLUMNS],
            $this->schema[Record::OUTER_KEY],
            $this->schema[Record::THOUGHT_INNER_KEY],
            $this->schema[Record::THOUGHT_OUTER_KEY]
        );

        return $node->asJoined($this->isJoined());
    }

    /**
     * Pivot table name.
     *
     * @return string
     */
    protected function pivotTable(): string
    {
        return $this->schema[Record::PIVOT_TABLE];
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
        return $this->schema[Record::PIVOT_COLUMNS];
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

    /**
     * Defined role to be used in morphed relations.
     *
     * @return string
     */
    private function targetRole(): string
    {
        return $this->targetRole ?? $this->orm->define(
                $this->parent->getClass(),
                ORMInterface::R_ROLE_NAME
            );
    }
}