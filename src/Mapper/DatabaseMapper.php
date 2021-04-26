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

/**
 * Provides basic capabilities to work with entities persisted in SQL databases.
 */
abstract class DatabaseMapper implements MapperInterface
{
    /** @var Select\SourceInterface */
    protected $source;

    /** @var ORMInterface */
    protected $orm;

    /** @var string */
    protected $role;

    /** @var array */
    protected $columns;

    /** @var array */
    protected $fields;

    /** @var string */
    protected $primaryKey;

    /** @var string */
    protected $primaryColumn;

    /** @var string[] */
    protected $primaryColumns = [];

    /** @var string[] */
    protected $primaryKeys;

    public function __construct(ORMInterface $orm, string $role)
    {
        if (!$orm instanceof Select\SourceProviderInterface) {
            throw new MapperException('Source factory is missing');
        }

        $this->orm = $orm;
        $this->role = $role;

        $this->source = $orm->getSource($role);
        $this->columns = $orm->getSchema()->define($role, Schema::COLUMNS);

        $primaryKeys = (array)$orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        $primaryKey = implode(':', $primaryKeys);
        $this->primaryKey = $primaryKey;
        $this->primaryColumn = $this->columns[$primaryKey] ?? $primaryKey;

        $this->primaryKeys = $primaryKeys;
        foreach ($this->primaryKeys as $PK) {
            $this->primaryColumns[] = $this->columns[$PK] ?? $PK;
        }

        // Resolve field names
        foreach ($this->columns as $name => $column) {
            $this->fields[] = is_numeric($name) ? $column : $name;
        }
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function queueCreate(object $entity, Node $node, State $state): ContextCarrierInterface
    {
        $columns = $this->fetchFields($entity);

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);
        $state->setData($columns);

        #todo
        $columns[$this->primaryKey] = $columns[$this->primaryKey] ?? $this->nextPrimaryKey();
        if ($columns[$this->primaryKey] === null) {
            unset($columns[$this->primaryKey]);
        }

        $insert = new Insert(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $this->mapColumns($columns),
            $this->primaryColumns
        );

        if (count($this->primaryKeys) === 1) {
            $key = isset($columns[$this->primaryKey]) ? $this->primaryColumn : Insert::INSERT_ID;
            $insert->forward($key, $state, $this->primaryKey);
        } else {
            foreach ($this->primaryKeys as $num => $pk) {
                $insert->forward($this->primaryColumns[$num], $state, $pk);
            }
        }

        return $insert;
    }

    public function queueUpdate(object $entity, Node $node, State $state): ContextCarrierInterface
    {
        $data = $this->fetchFields($entity);

        // in a future mapper must support solid states
        $changes = array_udiff_assoc($data, $state->getTransactionData(), [Node::class, 'compare']);
        foreach ($this->primaryKeys as $pk) {
            unset($changes[$pk]);
        }

        $changedColumns = $this->mapColumns($changes);

        $update = new Update($this->source->getDatabase(), $this->source->getTable(), $changedColumns);
        $state->setStatus(Node::SCHEDULED_UPDATE);
        $state->setData($changes);

        // we are trying to update entity without PK right now
        foreach ($this->primaryKeys as $i => $pk) {
            $state->forward(
                $pk,
                $update,
                $this->primaryColumns[$i],
                true,
                ConsumerInterface::SCOPE
            );
        }

        return $update;
    }

    public function queueDelete(object $entity, Node $node, State $state): CommandInterface
    {
        $delete = new Delete($this->source->getDatabase(), $this->source->getTable());
        $state->setStatus(Node::SCHEDULED_DELETE);
        $state->decClaim();

        $delete->waitScope(...$this->primaryColumns);
        foreach ($this->primaryKeys as $i => $key) {
            $state->forward(
                $key,
                $delete,
                $this->primaryColumns[$i],
                true,
                ConsumerInterface::SCOPE
            );
        }

        return $delete;
    }

    /**
     * Generate next sequential entity ID. Return null to use autoincrement value.
     *
     * @return mixed|null
     */
    protected function nextPrimaryKey()
    {
        return null;
    }

    /**
     * Get entity columns.
     *
     * @param object $entity
     * @return array
     */
    abstract protected function fetchFields(object $entity): array;

    /**
     * Map internal field names to database specific column names.
     *
     * @param array $columns
     * @return array
     */
    protected function mapColumns(array $columns): array
    {
        $result = [];
        foreach ($columns as $column => $value) {
            $result[$this->columns[$column] ?? $column] = $value;
        }

        return $result;
    }
}
