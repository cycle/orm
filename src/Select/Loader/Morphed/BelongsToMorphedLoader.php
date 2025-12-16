<?php

declare(strict_types=1);

namespace Cycle\ORM\Select\Loader\Morphed;

use Cycle\Database\Query\SelectQuery;
use Cycle\Database\StatementInterface;
use Cycle\ORM\FactoryInterface;
use Cycle\ORM\Parser\AbstractNode;
use Cycle\ORM\Parser\ProxyNode;
use Cycle\ORM\Parser\SingularNode;
use Cycle\ORM\Relation;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\AbstractLoader;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\ScopeInterface;
use Cycle\ORM\Select\Traits\ColumnsTrait;
use Cycle\ORM\Select\Traits\ScopeTrait;
use Cycle\ORM\Service\SourceProviderInterface;

/**
 * Creates an additional query constrain based on parent entity alias.
 */
final class BelongsToMorphedLoader extends AbstractLoader
{
    use ScopeTrait;
    use ColumnsTrait;

    /**
     * Loader that contains current loader
     */
    protected ?LoaderInterface $parent = null;

    protected array $options = [
        'load' => false,
        'scope' => true,
        'minify' => true,
    ];

    /** @var non-empty-string */
    private string $morphKey;

    /** @var list<non-empty-string> */
    private array $innerKey;

    /** @var list<non-empty-string> */
    private array $outerKey;

    /**
     * @param array<non-empty-string, mixed> $schema Relation schema
     */
    public function __construct(
        SchemaInterface $ormSchema,
        SourceProviderInterface $sourceProvider,
        FactoryInterface $factory,
        array $schema,
    ) {
        $this->ormSchema = $ormSchema;
        $this->sourceProvider = $sourceProvider;
        $this->factory = $factory;

        $this->morphKey = $schema[Relation::MORPH_KEY];
        $this->innerKey = (array) $schema[Relation::INNER_KEY];
        $this->outerKey = (array) $schema[Relation::OUTER_KEY];
    }

    public function getAlias(): string
    {
        return $this->target ?? throw new \RuntimeException('Target role is not defined yet.');
    }

    public function getTarget(): string
    {
        return $this->target ?? throw new \RuntimeException('Target role is not defined yet.');
    }

    public function initNode(): AbstractNode
    {
        return new ProxyNode([$this->morphKey, ...$this->innerKey]);
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

    public function isLoaded(): bool
    {
        // This loader is always loaded
        return true;
    }

    private function applyCriteria(SelectQuery $query, array $criteria): SelectQuery
    {
        // Map criteria to inner keys
        foreach ($this->innerKey as $i => $key) {
            $query->where($this->getAlias() . '.' . $this->fieldAlias($this->outerKey[$i]), $criteria[$key]);
        }

        return $query;
    }

    /**
     * Group references by their morph role.
     *
     * @return array<non-empty-string, array<non-empty-string, mixed>>
     */
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
        $self = $this->cloneForRole($role);
        $newNode = new SingularNode(
            columns: \array_keys($self->columns),
            primaryKeys: (array) $self->ormSchema->define($role, SchemaInterface::PRIMARY_KEY),
            innerKeys: $self->outerKey,
            outerKeys: [$self->morphKey, ...$self->innerKey],
            role: $role,
        );

        // Register this role in the morphed node
        $roleNode = $node->addNode($role, $newNode);

        // Build Query
        $query = $self->source->getDatabase()->select()->from(
            \sprintf('%s AS %s', $self->source->getTable(), $self->getAlias()),
        );

        // Scopes
        $self->scope = match (true) {
            $self->options['scope'] === true => $self->getSource()->getScope(),
            $self->options['scope'] instanceof ScopeInterface => $self->options['scope'],
            \is_string($self->options['scope']) => $self->factory->make($self->options['scope']),
            default => null,
        };

        // Configure WHERE IN condition
        $self->applyCriteria($query, $references);
        $self->mountColumns($query, $self->options['minify'], '', true);
        $self->configureQuery($query);

        // Execute query
        $statement = $query->run();

        // Parse fetched rows into the ROLE-SPECIFIC node
        foreach ($statement->fetchAll(StatementInterface::FETCH_NUM) as $row) {
            $roleNode->parseRow(0, $row);
        }

        $statement->close();
    }

    /**
     * @param non-empty-string $role
     */
    private function cloneForRole(string $role): self
    {
        $self = clone $this;
        $self->target = $role;
        $self->children = $self->ormSchema->getInheritedRoles($role);
        $self->source = $self->sourceProvider->getSource($role);
        $self->columns = $self->normalizeColumns($self->ormSchema->define($role, SchemaInterface::COLUMNS));

        return $self;
    }
}
