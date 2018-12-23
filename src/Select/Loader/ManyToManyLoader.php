<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Spiral\Cycle\Select\Loader;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\PivotedNode;
use Spiral\Cycle\Relation;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select\JoinableLoader;
use Spiral\Cycle\Select\SourceInterface;
use Spiral\Cycle\Select\Traits\WhereTrait;
use Spiral\Database\Injection\Parameter;
use Spiral\Database\Query\SelectQuery;

class ManyToManyLoader extends JoinableLoader
{
    use WhereTrait;

    /**
     * Default set of relation options. Child implementation might defined their of default options.
     *
     * @var array
     */
    protected $options = [
        'constrain'  => SourceInterface::DEFAULT_CONSTRAIN,
        'method'     => self::POSTLOAD,
        'minify'     => true,
        'alias'      => null,
        'pivotAlias' => null,
        'using'      => null,
        'where'      => null,
        'wherePivot' => null,
    ];

    /**
     * {@inheritdoc}
     */
    public function __construct(ORMInterface $orm, string $name, string $target, array $schema)
    {
        parent::__construct($orm, $name, $target, $schema);
        $this->options['where'] = $schema[Relation::WHERE] ?? [];
        $this->options['wherePivot'] = $schema[Relation::PIVOT_WHERE] ?? [];
    }

    /**
     * Pivot table name.
     *
     * @return string
     */
    public function getPivotTable(): string
    {
        return $this->schema[Relation::PIVOT_TABLE];
    }

    /**
     * Pivot table alias, depends on relation table alias.
     *
     * @return string
     */
    public function getPivotAlias(): string
    {
        if (!empty($this->options['pivotAlias'])) {
            return $this->options['pivotAlias'];
        }

        return $this->getAlias() . '_pivot';
    }

    /**
     * {@inheritdoc}
     */
    public function configureQuery(SelectQuery $query, array $outerKeys = []): SelectQuery
    {
        if (!empty($this->options['using'])) {
            //Use pre-defined query
            return parent::configureQuery($query, $outerKeys);
        }

        if ($this->isJoined()) {
            $query->join(
                $this->getJoinMethod(),
                $this->getPivotTable() . ' AS ' . $this->getPivotAlias())
                ->on(
                    $this->pivotKey(Relation::THOUGHT_INNER_KEY),
                    $this->parentKey(Relation::INNER_KEY)
                );
        } else {
            $query->innerJoin(
                $this->getPivotTable() . ' AS ' . $this->getPivotAlias()
            )->on(
                $this->pivotKey(Relation::THOUGHT_OUTER_KEY),
                $this->localKey(Relation::OUTER_KEY)
            )->where(
                $this->pivotKey(Relation::THOUGHT_INNER_KEY),
                new Parameter($outerKeys)
            );
        }

        // when relation is joined we will use ON statements, when not - normal WHERE
        $whereTarget = $this->isJoined() ? 'onWhere' : 'where';

        // pivot conditions specified in relation schema
        $this->setWhere(
            $query,
            $this->getPivotAlias(),
            $whereTarget,
            $this->define(Relation::PIVOT_WHERE)
        );

        // pivot conditions specified by user @todo: bug, table name is ignored
        $this->setWhere($query, $this->getPivotAlias(), $whereTarget, $this->options['wherePivot']);

        if ($this->isJoined()) {
            // actual data is always INNER join
            $query->join(
                $this->getJoinMethod(),
                $this->getJoinTable()
            )->on(
                $this->localKey(Relation::OUTER_KEY),
                $this->pivotKey(Relation::THOUGHT_OUTER_KEY)
            );
        }

        // where conditions specified in relation definition
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->define(Relation::WHERE));

        // user specified WHERE conditions
        $this->setWhere($query, $this->getAlias(), $whereTarget, $this->options['where']);

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
        $alias = $this->getPivotAlias();

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
     * @param int|string $key
     * @return null|string
     */
    protected function pivotKey($key): ?string
    {
        if (!isset($this->schema[$key])) {
            return null;
        }

        return $this->getPivotAlias() . '.' . $this->schema[$key];
    }
}