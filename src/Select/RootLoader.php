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

    protected array $options = [
        'load' => true,
        'scope' => true,
    ];
    private SelectQuery $query;
    private bool $forceGroupBy = false;

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
            \sprintf('%s AS %s', $this->table, $this->getAlias()),
        );
        $this->columns = $this->normalizeColumns($this->define(SchemaInterface::COLUMNS));

        if ($loadRelations) {
            foreach ($this->getEagerLoaders() as $relation) {
                $this->loadRelation($relation, [], false, true);
            }
        }
    }

    public function getAlias(): string
    {
        return $this->target;
    }

    /**
     * Primary column name list with table name like `table.column`.
     *
     * @return non-empty-string|non-empty-array<non-empty-string>
     */
    public function getPK(): array|string
    {
        /** @var non-empty-string|non-empty-array<non-empty-string> $pk */
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
        return (array) $this->define(SchemaInterface::PRIMARY_KEY);
    }

    /**
     * Return base query associated with the loader.
     */
    public function getQuery(): SelectQuery
    {
        return $this->query;
    }

    /**
     * Return the ordered list of column names produced by the root loader's
     * SELECT clause. Positions in this list correspond to positions in a
     * FETCH_NUM row from {@see buildQuery()} — useful for callers that want
     * to inspect raw rows before they reach the parser (e.g. cursor-based
     * streaming with parent-boundary chunking).
     *
     * @return non-empty-string[]
     */
    public function getColumnNames(): array
    {
        return $this->columnNames();
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

        $this->parseRows($node, $statement->fetchAll(StatementInterface::FETCH_NUM));

        $statement->close();

        $this->loadChildren($node, $includeRole);
    }

    /**
     * Push a batch of raw rows through the parser into the given node.
     *
     * Separated from {@see loadData()} so callers that produce their own row stream
     * (e.g. cursor-based streaming) can reuse the parsing step independently of
     * query execution and child-loader orchestration.
     *
     * @param iterable<array<int, mixed>> $rows Rows in FETCH_NUM (positional) shape.
     */
    public function parseRows(AbstractNode $node, iterable $rows): void
    {
        foreach ($rows as $row) {
            $node->parseRow(0, $row);
        }
    }

    /**
     * Run all child loaders (POSTLOAD relations, inheritance) against the given node.
     *
     * Designed to be called after {@see parseRows()} on a node that already holds
     * the parent rows of a chunk. Child loaders aggregate parent keys from the node's
     * index and issue their own queries.
     */
    public function loadChildren(AbstractNode $node, bool $includeRole = false): void
    {
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

    /**
     * Add selected columns to GROUP BY clause.
     *
     * Might be useful when deduplication is required because of JOINs or other conditions.
     *
     * @param bool $force When set to true, GROUP BY will be forced.
     */
    public function forceGroupBy(bool $force = true): void
    {
        $this->forceGroupBy = $force;
    }

    /**
     * Clone the underlying query.
     */
    public function __clone()
    {
        $this->query = clone $this->query;
        parent::__clone();
    }

    protected function configureQuery(SelectQuery $query): SelectQuery
    {
        return parent::configureQuery(
            $this->mountColumns($query, true, '', true, $this->forceGroupBy),
        );
    }

    protected function initNode(): RootNode
    {
        return new RootNode($this->columnNames(), (array) $this->define(SchemaInterface::PRIMARY_KEY));
    }
}
