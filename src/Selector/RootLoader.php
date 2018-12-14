<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Selector;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\RootNode;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector\Traits\ColumnsTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 */
class RootLoader extends AbstractLoader
{
    use ColumnsTrait;

    /** @var null|array */
    private $columns = null;

    /** @var SelectQuery */
    private $query;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     */
    public function __construct(ORMInterface $orm, string $target)
    {
        parent::__construct($orm, $target);

        $source = $this->getSource();

        $this->query = $source->getDatabase()->select()->from(
            sprintf("%s AS %s", $source->getTable(), $this->getAlias())
        );

        $this->scope = $source->getScope(SourceInterface::DEFAULT_SCOPE);
    }

    /**
     * @inheritdoc
     */
    public function getAlias(): string
    {
        return $this->orm->getSchema()->define($this->target, Schema::ALIAS);
    }

    /**
     * Get primary key column identifier (aliased).
     *
     * @return string
     */
    public function getPK(): string
    {
        return $this->getAlias() . '.' . $this->define(Schema::PRIMARY_KEY);
    }

    /**
     * Columns to be selected, please note, primary key will always be included, DO not include
     * column aliases in here, aliases will be added automatically. Creates new loader tree copy.
     *
     * @param array $columns
     * @return RootLoader
     */
    public function withColumns(array $columns): self
    {
        $loader = clone $this;

        // always include primary key
        $loader->columns = array_merge([$loader->define(Schema::PRIMARY_KEY)], $columns);

        return $loader;
    }

    /**
     * Return base query associated with the loader. Mutable.
     *
     * @return SelectQuery
     */
    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Compile query with all needed conditions, columns and etc.
     *
     * @return SelectQuery
     */
    public function buildQuery(): SelectQuery
    {
        return $this->configureQuery(clone $this->query);
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        $statement = $this->buildQuery()->run();
        $statement->setFetchMode(\PDO::FETCH_NUM);

        foreach ($statement as $row) {
            $node->parseRow(0, $row);
        }

        $statement->close();

        // loading child datasets
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation));
        }
    }

    /**
     * Clone with initial query.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        parent::__clone();
    }

    /**
     * {@inheritdoc}
     */
    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        return parent::configureQuery(
            $this->mountColumns($query, true, '', true)
        );
    }

    /**
     * @inheritdoc
     */
    protected function initNode(): AbstractNode
    {
        return new RootNode($this->getColumns(), $this->define(Schema::PRIMARY_KEY));
    }

    /**
     * @inheritdoc
     */
    protected function getColumns(): array
    {
        return $this->columns ?? $this->define(Schema::COLUMNS);
    }
}