<?php

declare(strict_types=1);

namespace Cycle\ORM\Mapper;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Command\Database\Delete;
use Cycle\ORM\Command\Database\Insert;
use Cycle\ORM\Command\Database\Update;
use Cycle\ORM\Context\ConsumerInterface;
use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;
use Cycle\ORM\MapperInterface;
use Cycle\ORM\ORMInterface;
use Cycle\ORM\Schema;
use Cycle\ORM\Select;
use Cycle\ORM\Select\SourceInterface;

/**
 * Provides basic capabilities to work with entities persisted in SQL databases.
 */
abstract class DatabaseMapper implements MapperInterface
{
    protected SourceInterface $source;

    protected ORMInterface $orm;

    protected string $role;

    protected array $columns;

    /** @var array */
    protected $fields;

    /** @var string[] */
    protected array $primaryColumns = [];

    /** @var string[] */
    protected array $primaryKeys;

    public function __construct(ORMInterface $orm, string $role)
    {
        if (!$orm instanceof Select\SourceProviderInterface) {
            throw new MapperException('Source factory is missing');
        }

        $this->orm = $orm;
        $this->role = $role;

        $this->source = $orm->getSource($role);
        $this->columns = $orm->getSchema()->define($role, Schema::COLUMNS);

        $this->primaryKeys = (array)$orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        foreach ($this->primaryKeys as $PK) {
            $this->primaryColumns[] = $this->columns[$PK] ?? $PK;
        }

        // Resolve field names
        foreach ($this->columns as $name => $column) {
            $this->fields[] = is_string($name) ? $name : $column;
        }
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function queueCreate(object $entity, Node $node, State $state): ContextCarrierInterface
    {
        // $values = $node->getData() + $this->fetchFields($entity);
        $values = $node->getChanges();

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);

        foreach ($this->primaryKeys as $key) {
            if (!isset($values[$key])) {
                $values = array_merge($values, $this->nextPrimaryKey() ?? []);
                break;
            }
        }
        // $state->setTransactionData($values);
        // $state->setData($values);

        $insert = new Insert(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $state,
            $this->primaryKeys,
            [$this, 'mapColumns']
        );

        // if (count($this->primaryKeys) === 1) {
        //     $key = $this->primaryKeys[0];
        //     $column = isset($values[$key]) ? $key : Insert::INSERT_ID;
            // $insert->forward($column, $state, $key);
        // } else {
            // foreach ($this->primaryKeys as $num => $pk) {
            //     $insert->forward($this->primaryColumns[$num], $state, $pk);
            // }
        // }

        return $insert;
    }

    public function queueUpdate(object $entity, Node $node, State $state): ContextCarrierInterface
    {
        $fromData = $node->getState()->getTransactionData();
        // $fromData = $node->getInitialData();
        // $data = $this->fetchFields($entity);
        //
        // // in a future mapper must support solid states
        // $changes = array_udiff_assoc($data, $fromData, [Node::class, 'compare']);
        $changes = $node->getChanges();
        echo "changes count: " . count($changes) . "\n";
        // $state->setTransactionData($changes);
        // $state->setData($changes);
        // Calc scope

        $update = new Update(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $node,
            $this->primaryKeys,
            [$this, 'mapColumns']
        );

        foreach ($this->primaryKeys as $pk) {
            if (isset($fromData[$pk])) {
                // set update criteria right now
                $update->register($pk, $fromData[$pk], false, ConsumerInterface::SCOPE);
            } else {
                // subscribe to PK update
                $state->forward($pk, $update, $pk, true, ConsumerInterface::SCOPE);
            }
        }

        return $update;
    }

    public function queueDelete(object $entity, Node $node, State $state): CommandInterface
    {
        $delete = new Delete($this->source->getDatabase(), $this->source->getTable(), $state, [$this, 'mapColumns']);
        $state->setStatus(Node::SCHEDULED_DELETE);
        $state->decClaim();

        $delete->waitScope(...$this->primaryKeys);
        $fromData = $node->getInitialData();
        foreach ($this->primaryKeys as $pk) {
            if (isset($fromData[$pk])) {
                // set update criteria right now
                $delete->register($pk, $fromData[$pk], false, ConsumerInterface::SCOPE);
            } else {
                // subscribe to PK update
                $state->forward($pk, $delete, $pk, true, ConsumerInterface::SCOPE);
            }
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

    public function mapColumns(array $values): array
    {
        $result = [];
        foreach ($values as $column => $value) {
            $result[$this->columns[$column] ?? $column] = $value;
        }

        return $result;
    }
}
