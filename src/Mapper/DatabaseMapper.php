<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Parser\CastableInterface;
use Cycle\ORM\Parser\TypecastInterface;
use Cycle\ORM\Parser\UncastableInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Service\TypecastProviderInterface;
use Cycle\ORM\RelationMap;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select\SourceInterface;

/**
 * Provides basic capabilities to work with entities persisted in SQL databases.
 */
abstract class DatabaseMapper implements MapperInterface
{
    protected SourceInterface $source;
    protected array $columns = [];
    protected array $parentColumns = [];

    /** @var string[] */
    protected array $primaryColumns = [];

    /** @var string[] */
    protected array $primaryKeys;

    protected RelationMap $relationMap;
    private ?TypecastInterface $typecast;

    /** @var array<non-empty-string, int> */
    private array $generatedFields;

    public function __construct(
        ORMInterface $orm,
        protected string $role,
    ) {
        $this->source = $orm->getSource($role);
        $this->typecast = $orm->getService(TypecastProviderInterface::class)->getTypecast($role);
        $this->relationMap = $orm->getService(RelationProviderInterface::class)->getRelationMap($role);

        $schema = $orm->getSchema();
        foreach ($schema->define($role, SchemaInterface::COLUMNS) as $property => $column) {
            $this->columns[\is_int($property) ? $column : $property] = $column;
        }

        $this->generatedFields = $schema->define($role, SchemaInterface::GENERATED_FIELDS) ?? [];
        // Parent's fields
        $parent = $schema->define($role, SchemaInterface::PARENT);
        while ($parent !== null) {
            foreach ($schema->define($parent, SchemaInterface::COLUMNS) as $property => $column) {
                $this->parentColumns[\is_int($property) ? $column : $property] = $column;
            }
            $parent = $schema->define($parent, SchemaInterface::PARENT);
        }

        $this->primaryKeys = (array) $schema->define($role, SchemaInterface::PRIMARY_KEY);
        foreach ($this->primaryKeys as $PK) {
            $this->primaryColumns[] = $this->columns[$PK] ?? $PK;
        }
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function cast(array $data): array
    {
        if ($this->typecast instanceof CastableInterface) {
            $data = $this->typecast->cast($data);
        }

        // Cast relations
        foreach ($this->relationMap->getRelations() as $field => $relation) {
            if (!\array_key_exists($field, $data)) {
                continue;
            }
            $value = $data[$field];
            if (!\is_array($value) && $value !== null) {
                continue;
            }
            // break links
            unset($data[$field]);
            $data[$field] = $relation->cast($value);
        }

        return $data;
    }

    public function uncast(array $data): array
    {
        if (!$this->typecast instanceof UncastableInterface) {
            return $data;
        }

        return $this->typecast->uncast($data);
    }

    public function queueCreate(object $entity, Node $node, State $state): CommandInterface
    {
        $values = $state->getData();

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);

        foreach ($this->primaryKeys as $key) {
            if (!isset($values[$key])) {
                foreach ($this->nextPrimaryKey() ?? [] as $pk => $value) {
                    $state->register($pk, $value);
                }
                break;
            }
        }

        return new Insert(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $state,
            $this,
            $this->primaryKeys,
            \count($this->primaryColumns) === 1 ? $this->primaryColumns[0] : null,
            $this->generatedFields,
        );
    }

    public function queueUpdate(object $entity, Node $node, State $state): CommandInterface
    {
        $fromData = $state->getTransactionData();

        $update = new Update(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $state,
            $this,
            $this->primaryKeys,
        );

        foreach ($this->primaryKeys as $pk) {
            // set update criteria right now
            $update->setScope($pk, $fromData[$pk]);
        }

        return $update;
    }

    public function queueDelete(object $entity, Node $node, State $state): CommandInterface
    {
        $delete = new Delete(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $state,
            $this,
        );

        $state->setStatus(Node::SCHEDULED_DELETE);

        $delete->waitScope(...$this->primaryKeys);
        $fromData = $node->getData();
        foreach ($this->primaryKeys as $pk) {
            // set update criteria right now
            $delete->setScope($pk, $fromData[$pk]);
        }

        return $delete;
    }

    public function mapColumns(array &$values): array
    {
        $result = [];
        foreach ($values as $column => $value) {
            if (isset($this->columns[$column])) {
                $result[$this->columns[$column]] = $value;
            } else {
                unset($values[$column]);
            }
        }

        return $result;
    }

    /**
     * Generate next sequential entity ID. Return null to use autoincrement value.
     */
    protected function nextPrimaryKey(): ?array
    {
        return null;
    }
}
