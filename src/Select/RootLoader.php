<?php

declare(strict_types=1);

namespace Cycle\ORM\Select;

use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\RootNode;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;

/**
 * Primary ORM loader. Loader wraps at top of select query in order to modify it's conditions, joins
 * and etc based on nested loaders.
 *
 * Root load does not load constrain from ORM by default.
 *
 * @method RootNode createNode()
 *
 * @internal
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

    /**
     * @param bool $loadRelations Define loading eager relations and JTI hierarchy.
     */
    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        string $target,
        bool $loadRelations = true,
    ) {
        parent::__construct($ormSchema, $sourceProvider, $factory, $target);
        $this->query = $this->source->getDatabase()->select()->from(
            sprintf('%s AS %s', $this->source->getTable(), $this->getAlias())
        );
        $this->columns = $this->normalizeColumns($this->define(SchemaInterface::COLUMNS));

        if ($loadRelations) {
            foreach ($this->getEagerLoaders() as $relation) {
                $this->loadRelation($relation, [], false, true);
            }
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
     * Primary column name list with table name like `table.column`.
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
     * Get list of primary fields.
     *
     * @return list<non-empty-string>
     */
    public function getPrimaryFields(): array
    {
        return (array)$this->define(SchemaInterface::PRIMARY_KEY);
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

        $this->loadHierarchy($node, $includeRole);
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
