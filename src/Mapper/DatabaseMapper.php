<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\SchemaInterface;
use Cycle\ORM\Select;
use Cycle\ORM\Select\SourceInterface;

/**
 * Provides basic capabilities to work with entities persisted in SQL databases.
 */
abstract class DatabaseMapper implements MapperInterface
{
    protected SourceInterface $source;

    protected array $columns;

    protected array $parentColumns = [];

    /** @var string[] */
    protected array $primaryColumns = [];

    /** @var string[] */
    protected array $primaryKeys;

    public function __construct(
        protected ORMInterface $orm,
        protected string $role
    ) {
        $this->source = $orm->getSource($role);
        foreach ($orm->getSchema()->define($role, SchemaInterface::COLUMNS) as $property => $column) {
            $this->columns[\is_int($property) ? $column : $property] = $column;
        }

        // Parent's fields
        $parent = $orm->getSchema()->define($role, SchemaInterface::PARENT);
        while ($parent !== null) {
            foreach ($orm->getSchema()->define($parent, SchemaInterface::COLUMNS) as $property => $column) {
                $this->parentColumns[\is_int($property) ? $column : $property] = $column;
            }
            $parent = $orm->getSchema()->define($parent, SchemaInterface::PARENT);
        }

        $this->primaryKeys = (array)$orm->getSchema()->define($role, SchemaInterface::PRIMARY_KEY);
        foreach ($this->primaryKeys as $PK) {
            $this->primaryColumns[] = $this->columns[$PK] ?? $PK;
        }
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function queueCreate(object $entity, Node $node, State $state): CommandInterface
    {
        $values = $node->getData();

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
            $this->primaryKeys,
            \count($this->primaryColumns) === 1 ? $this->primaryColumns[0] : null,
            [$this, 'mapColumns']
        );
    }

    public function queueUpdate(object $entity, Node $node, State $state): CommandInterface
    {
        $fromData = $state->getTransactionData();
        \Cycle\ORM\Transaction\Pool::DEBUG AND print "changes count: " . \count($node->getChanges()) . "\n";

        $update = new Update(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $state,
            $this->primaryKeys,
            [$this, 'mapColumns']
        );

        foreach ($this->primaryKeys as $pk) {
            // set update criteria right now
            $update->register($pk, $fromData[$pk], ConsumerInterface::SCOPE);
        }

        return $update;
    }

    public function queueDelete(object $entity, Node $node, State $state): CommandInterface
    {
        $delete = new Delete($this->source->getDatabase(), $this->source->getTable(), $state, [$this, 'mapColumns']);
        $state->setStatus(Node::SCHEDULED_DELETE);

        $delete->waitScope(...$this->primaryKeys);
        $fromData = $node->getInitialData();
        foreach ($this->primaryKeys as $pk) {
            // set update criteria right now
            $delete->register($pk, $fromData[$pk], ConsumerInterface::SCOPE);
        }

        return $delete;
    }

    /**
     * Generate next sequential entity ID. Return null to use autoincrement value.
     */
    protected function nextPrimaryKey(): ?array
    {
        return null;
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
}
