<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Loader;

use Spiral\Database\Query\SelectQuery;
use Spiral\ORM\Loader\Traits\ColumnsTrait;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Schema;
use Spiral\ORM\TreeGenerator\AbstractNode;
use Spiral\ORM\TreeGenerator\RootNode;

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
     * @param string       $class
     */
    public function __construct(ORMInterface $orm, string $class)
    {
        parent::__construct($orm, $class);

        $this->query = $this->getDatabase()->select()->from(sprintf(
            "%s AS %s",
            $this->define(Schema::TABLE),
            $this->getAlias()
        ));
    }

    /**
     * @inheritdoc
     */
    public function getAlias(): string
    {
        return $this->orm->getSchema()->define($this->class, Schema::ALIAS);
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
    public function compileQuery(): SelectQuery
    {
        return $this->configureQuery(clone $this->query);
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
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        $statement = $this->compileQuery()->run();
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