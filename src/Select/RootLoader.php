<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;
use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 *
 * Root load does not load constrain from ORM by default.
 *
 * @method RootNode createNode()
 */
final class RootLoader extends AbstractLoader
{
    use ColumnsTrait;
    use ScopeTrait;

    /** @var array */
    protected array $options = [
        'load' => true,
        'scope' => true,
    ];

    private SelectQuery $query;

    public function __construct(ORMInterface $orm, string $target)
    {
        parent::__construct($orm, $target);
        $this->query = $this->getSource()->getDatabase()->select()->from(
            sprintf('%s AS %s', $this->getSource()->getTable(), $this->getAlias())
        );
        $this->columns = $this->define(SchemaInterface::COLUMNS);

        foreach ($this->getEagerLoaders() as $relation) {
            $this->loadRelation($relation, [], false, true);
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

    public function getAlias(): string
    {
        return $this->target;
    }

    /**
     * Get primary key column identifier (aliased).
     *
     * @return string|string[]
     */
    public function getPK(): array|string
    {
        $pk = $this->define(SchemaInterface::PRIMARY_KEY);
        if (\is_array($pk)) {
            $result = [];
            foreach ($pk as $key) {
                $result[] = $this->getAlias() . '.' . $this->fieldAlias($key);
            }
            return $result;
        }

        return $this->getAlias() . '.' . $this->fieldAlias($pk);
    }

    /**
     * Return base query associated with the loader.
     */
    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Compile query with all needed conditions, columns and etc.
     */
    public function buildQuery(): SelectQuery
    {
        return $this->configureQuery(clone $this->query);
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        $statement = $this->buildQuery()->run();

        foreach ($statement->fetchAll(StatementInterface::FETCH_NUM) as $row) {
            $node->parseRow(0, $row);
        }

        $statement->close();

        // loading child datasets
        foreach ($this->load as $relation => $loader) {
            $loader->loadData($node->getNode($relation), $includeRole);
        }

        $this->loadIerarchy($node, $includeRole);
    }

    public function isLoaded(): bool
    {
        // root loader is always loaded
        return true;
    }

    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        return parent::configureQuery(
            $this->mountColumns($query, true, '', true)
        );
    }

    protected function initNode(): RootNode
    {
        return new RootNode($this->columnNames(), (array)$this->define(SchemaInterface::PRIMARY_KEY));
    }
}
