<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader\Morphed;

use Cycle\Database\Query\SelectQuery;
use Cycle\ORM\Exception\LoaderException;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ProxyNode;
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\RootLoader;
use Cycle\ORM\Service\SourceProviderInterface;
use JetBrains\PhpStorm\Pure;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
final class BelongsToMorphedLoader implements LoaderInterface
{
    /**
     * Loader that contains current loader
     */
    protected ?LoaderInterface $parent = null;

    protected array $options = [
        'load' => false,
        'scope' => true,
        'minify' => true,
    ];
    private ProxyNode $node;

    /** @var non-empty-string */
    private string $morphKey;

    /** @var array<non-empty-string> */
    private array $innerKey;

    /** @var array<non-empty-string> */
    private array $outerKey;

    /**
     * @param class-string $target Target entity interface
     * @param array<non-empty-string, mixed> $schema Relation schema
     */
    public function __construct(
        private SchemaInterface $ormSchema,
        private SourceProviderInterface $sourceProvider,
        private FactoryInterface $factory,
        private string $target,
        array $schema,
    ) {
        $this->morphKey = $schema[Relation::MORPH_KEY];
        $this->innerKey = (array) $schema[Relation::INNER_KEY];
        $this->outerKey = (array) $schema[Relation::OUTER_KEY];
        $this->node = new ProxyNode([$this->morphKey, ...$this->innerKey]);
    }

    /**
     * Return the relation alias.
     */
    public function getAlias(): string
    {
        throw new \RuntimeException('Not implemented');
    }

    /**
     * Loader specific entity class.
     */
    public function getTarget(): string
    {
        return $this->target;
    }

    /**
     * Get column name related to internal key.
     */
    public function fieldAlias(string $field): ?string
    {
        throw new \RuntimeException('Not implemented');
    }

    public function withContext(LoaderInterface $parent, array $options = []): static
    {
        // check that given options are known
        if (!empty($wrong = \array_diff(\array_keys($options), \array_keys($this->options)))) {
            throw new LoaderException(
                \sprintf(
                    'Relation %s does not support option: %s',
                    $this::class,
                    \implode(', ', $wrong),
                ),
            );
        }

        $loader = clone $this;
        $loader->parent = $parent;
        $loader->options = $options + $this->options;

        return $loader;
    }

    public function createNode(): AbstractNode
    {
        return $this->node;
    }

    public function loadData(AbstractNode $node, bool $includeRole = false): void
    {
        // Get references from the parent node
        $references = $node->getReferenceValues();

        // Group references by their role (morph type)
        $groupedByRole = $this->groupReferencesByRole($references);

        // Load data for each role
        foreach ($groupedByRole as $role => $roleReferences) {
            $this->loadRoleData($node, $role, $roleReferences);
        }
    }

    public function setSubclassesLoading(bool $enabled): void {}

    public function isHierarchical(): bool
    {
        return false;
    }

    /**
     * @param non-empty-string $role
     * @param array<non-empty-string, non-empty-string> $columns Normalized columns
     */
    private function applyCriteria(SelectQuery $query, array $criteria): SelectQuery
    {
        // Map criteria to inner keys
        $where = [];
        foreach ($this->innerKey as $i => $key) {
            $where[$this->outerKey[$i]] = $criteria[$key];
        }

        $query->where(\key($where), \reset($where));

        return $query;
    }

    private function groupReferencesByRole(array $references): array
    {
        $grouped = [];
        foreach ($references as $ref) {
            $role = $ref[$this->morphKey];
            unset($ref[$this->morphKey]);
            $grouped[$role] = $ref;
        }

        return $grouped;
    }

    /**
     * Load data for a specific role.
     *
     * @param non-empty-string $role
     */
    private function loadRoleData(
        AbstractNode $node,
        string $role,
        array $references,
    ): void {
        $columns = $this->normalizeColumns($this->ormSchema->define($role, SchemaInterface::COLUMNS));
        $pk = (array) $this->ormSchema->define($role, SchemaInterface::PRIMARY_KEY);
        $newNode = new SingularNode(\array_keys($columns), $pk, $this->outerKey, [$this->morphKey, ...$this->innerKey], $role);

        // Register this role in the morphed node
        $roleNode = $node->addNode($role, $newNode);

        // Create loader for the specific role
        $loader = new RootLoader(
            $this->ormSchema,
            $this->sourceProvider,
            $this->factory,
            $role,
            loadRelations: false, // Don't auto-load eager relations
        );

        // Configure query with WHERE IN condition
        $query = $loader->getQuery();
        $this->applyCriteria($query, $references);
        $this->mountColumns($query, $role, $columns, $this->options['minify'], '', true);

        // Execute query
        $statement = $query->run();

        // Parse fetched rows into the ROLE-SPECIFIC node
        foreach ($statement->fetchAll(\Cycle\Database\StatementInterface::FETCH_NUM) as $row) {
            $roleNode->parseRow(0, $row);
        }

        $statement->close();
    }

    /**
     * Set columns into SelectQuery.
     *
     * @param non-empty-string $alias Table alias
     * @param array<non-empty-string, non-empty-string> $columns Normalized columns
     * @param bool $minify Minify column names (will work in case when query parsed in FETCH_NUM mode).
     * @param string $prefix Prefix to be added for each column name.
     * @param bool $overwrite When set to true existed columns will be removed.
     * @param bool $addToGroup When set to true columns will be added to GROUP BY clause.
     */
    private function mountColumns(
        SelectQuery $query,
        string $alias,
        array $columns,
        bool $minify = false,
        string $prefix = '',
        bool $overwrite = false,
        bool $addToGroup = false,
    ): SelectQuery {
        $cols = $overwrite ? [] : $query->getColumns();
        $i = 0;
        foreach ($columns as $internal => $external) {
            $name = $minify ? 'c' . ($i++) : $internal;
            $cols[] = "{$alias}.{$external} AS {$prefix}{$name}";
            $addToGroup and $query->groupBy("{$alias}.{$external}");
        }

        return $query->columns($cols);
    }

    /**
     * @param non-empty-string[] $columns
     *
     * @return array<non-empty-string, non-empty-string>
     *
     * @psalm-pure
     */
    #[Pure]
    private function normalizeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $alias => $column) {
            $result[\is_int($alias) ? $column : $alias] = $column;
        }

        return $result;
    }
}
