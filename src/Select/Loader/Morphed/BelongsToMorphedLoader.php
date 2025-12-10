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

    protected function configureQuery(SelectQuery $query, array $criteria): SelectQuery
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

    private function loadRoleData(
        AbstractNode $node,
        string $role,
        array $references,
    ): void {
        $columns = $this->normalizeColumns($this->ormSchema->define($role, SchemaInterface::COLUMNS));
        $pk = (array) $this->ormSchema->define($role, SchemaInterface::PRIMARY_KEY);
        $newNode = new SingularNode($columns, $pk, $this->outerKey, [$this->morphKey, ...$this->innerKey], $role);

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

        // Ensure all nested relations
        // todo @see src/Select/JoinableLoader.php:134
        // $query = $this->initQuery($role);

        // Configure query with WHERE IN condition
        $query = $loader->getQuery();
        $this->configureQuery($query, $references);

        // $node = $loader->createNode();
        // $loader->loadData($node, includeRole: true);

        // Execute query
        $statement = $query->run();

        // Parse fetched rows into the ROLE-SPECIFIC node
        foreach ($statement->fetchAll(\Cycle\Database\StatementInterface::FETCH_NUM) as $row) {
            $roleNode->parseRow(0, $row);
        }

        $statement->close();
    }

    private function normalizeColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $alias => $column) {
            $result[] = \is_int($alias) ? $column : $alias;
        }

        return $result;
    }
}
