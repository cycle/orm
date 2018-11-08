<?php
/**
 * Spiral, Core Components
 *
 * @author Wolfy-J
 */

namespace Spiral\Treap\Loader;

use Spiral\Database\Builders\SelectQuery;
use Spiral\Treap\Loader\Traits\ColumnsTrait;
use Spiral\ORM\Entities\Nodes\AbstractNode;
use Spiral\ORM\Entities\Nodes\RootNode;
use Spiral\ORM\ORMInterface;
use Spiral\ORM\Record;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 *
 * Note, in RootLoader schema represent record schema since there is no self to self relation.
 */
class RootLoader extends AbstractLoader
{
    use ColumnsTrait;

    /**
     * Root loader always define primary SelectQuery.
     *
     * @var SelectQuery
     */
    private $query;

    /**
     * Only columns to be selected, by default all columns.
     *
     * @var null|array
     */
    private $columns = null;

    /**
     * @param string       $class
     * @param array        $schema Record schema for root loader.
     * @param ORMInterface $orm
     */
    public function __construct(string $class, array $schema, ORMInterface $orm)
    {
        //Constructing with truncated schema
        parent::__construct(
            $class,
            [
                Record::SH_PRIMARY_KEY   => $schema[Record::SH_PRIMARY_KEY],
                Record::RELATION_COLUMNS => array_keys($schema[Record::SH_DEFAULTS])
            ],
            $orm
        );

        //Getting our initial select query
        $this->query = $orm->table($class)->select();
    }

    /**
     * Columns to be selected, please note, primary will always be included, DO not include
     * column aliases in here, aliases will be added automatically. Creates new loader tree copy.
     *
     * @param array $columns
     *
     * @return RootLoader
     */
    public function withColumns(array $columns): self
    {
        $loader = clone $this;
        $loader->columns = array_merge(
            [$loader->schema[Record::SH_PRIMARY_KEY]],
            $columns
        );

        return $loader;
    }

    /**
     * Return initial loader query (attention, mutable instance).
     *
     * @return SelectQuery
     */
    public function initialQuery(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Return build version of query.
     *
     * @return SelectQuery
     */
    public function compiledQuery(): SelectQuery
    {
        return $this->configureQuery(clone $this->query);
    }

    /**
     * Get primary key column if possible (aliased). Null when key is missing or non singular.
     *
     * @return string|null
     */
    public function primaryKey(): string
    {
        return $this->getAlias() . '.' . $this->schema[Record::SH_PRIMARY_KEY];
    }

    /**
     * We are using model role as alias. Visibility up.
     *
     * @return string
     */
    public function getAlias(): string
    {
        return $this->orm->define($this->class, ORMInterface::R_ROLE_NAME);
    }

    /**
     * @param SelectQuery $query
     *
     * @return SelectQuery
     */
    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        //Clarifying table name
        $query->from("{$this->getTable()} AS {$this->getAlias()}");

        //Columns to be loaded for primary model
        $this->mountColumns($query, true, '', true);

        return parent::configureQuery($query);
    }

    /**
     * {@inheritdoc}
     */
    public function loadData(AbstractNode $node)
    {
        //Fetching results from database
        $statement = $this->configureQuery(clone $this->query)->run();
        $statement->setFetchMode(\PDO::FETCH_NUM);

        foreach ($statement as $row) {
            $node->parseRow(0, $row);
        }

        //Destroying statement
        $statement->close();

        //Executing child loaders
        foreach ($this->loaders as $relation => $loader) {
            $loader->loadData($node->fetchNode($relation));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function initNode(): AbstractNode
    {
        return new RootNode(
            $this->getColumns(),
            $this->schema[Record::SH_PRIMARY_KEY]
        );
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
     * Relation columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->columns ?? $this->schema[Record::RELATION_COLUMNS];
    }
}