<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Select;

use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Parser\AbstractNode;
use Spiral\Cycle\Parser\RootNode;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select\Traits\ColumnsTrait;
use Spiral\Database\Query\SelectQuery;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 */
final class RootLoader extends AbstractLoader
{
    use ColumnsTrait;

    /** @var SelectQuery */
    private $query;

    /**
     * @param ORMInterface $orm
     * @param string       $target
     */
    public function __construct(ORMInterface $orm, string $target)
    {
        parent::__construct($orm, $target);
        $this->query = $this->getSource()->getDatabase()->select()->from($this->getSourceTable());
    }

    /**
     * @inheritdoc
     */
    public function getAlias(): string
    {
        return $this->target;
    }

    /**
     * Get primary key column identifier (aliased).
     *
     * @return string
     */
    public function getPK(): string
    {
        return $this->getAlias() . '.' . $this->columnName($this->define(Schema::PRIMARY_KEY));
    }

    /**
     * Return base query associated with the loader.
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
     * Clone the underlying query.
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
        return new RootNode($this->columnNames(), $this->define(Schema::PRIMARY_KEY));
    }

    /**
     * Relation columns.
     *
     * @return array
     */
    protected function getColumns(): array
    {
        return $this->define(Schema::COLUMNS);
    }

    /**
     * @return string
     */
    protected function getSourceTable(): string
    {
        return sprintf("%s AS %s", $this->getSource()->getTable(), $this->getAlias());
    }
}