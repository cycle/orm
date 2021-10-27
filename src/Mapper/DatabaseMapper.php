<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        if (!$orm instanceof Select\SourceProviderInterface) {
            throw new MapperException('Source factory is missing');
        }

        $this->orm = $orm;
        $this->role = $role;

        $this->source = $orm->getSource($role);
        $this->columns = $orm->getSchema()->define($role, Schema::COLUMNS);
        $this->primaryKey = $orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        $this->primaryColumn = $this->columns[$this->primaryKey] ?? $this->primaryKey;

        // Resolve field names
        foreach ($this->columns as $name => $column) {
            $this->fields[] = is_numeric($name) ? $column : $name;
        }
    }

    /**
     * @inheritdoc
     */
    public function getRole(): string
    {
        return $this->role;
    }

    /**
     * @inheritdoc
     */
    public function queueCreate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $columns = $this->fetchFields($entity);

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);
        $state->setData($columns);

        $columns[$this->primaryKey] = $columns[$this->primaryKey] ?? $this->nextPrimaryKey();
        if ($columns[$this->primaryKey] === null) {
            unset($columns[$this->primaryKey]);
        }

        $insert = new Insert(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $this->mapColumns($columns),
            $this->primaryColumn
        );

        $key = isset($columns[$this->primaryKey]) ? $this->primaryColumn : Insert::INSERT_ID;
        $insert->forward($key, $state, $this->primaryKey);

        return $insert;
    }

    /**
     * @inheritdoc
     */
    public function queueUpdate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $fromData = $state->getTransactionData();
        $changes = $node->getChanges($this->fetchFields($entity), $fromData);

        $state->setStatus(Node::SCHEDULED_UPDATE);
        $state->setData($changes);

        $update = new Update($this->source->getDatabase(), $this->source->getTable(), $this->mapColumns($changes));
        if (isset($fromData[$this->primaryKey])) {
            // set update criteria right now
            $update->register($this->primaryColumn, $fromData[$this->primaryKey], false, ConsumerInterface::SCOPE);
        } else {
            // subscribe to PK update
            $state->forward($this->primaryKey, $update, $this->primaryColumn, true, ConsumerInterface::SCOPE);
        }

        return $update;
    }

    /**
     * @inheritdoc
     */
    public function queueDelete($entity, Node $node, State $state): CommandInterface
    {
        $delete = new Delete($this->source->getDatabase(), $this->source->getTable());
        $state->setStatus(Node::SCHEDULED_DELETE);
        $state->decClaim();

        $delete->waitScope($this->primaryColumn);
        $state->forward(
            $this->primaryKey,
            $delete,
            $this->primaryColumn,
            true,
            ConsumerInterface::SCOPE
        );

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
     *
     * @return array
     */
    abstract protected function fetchFields($entity): array;

    /**
     * Map internal field names to database specific column names.
     *
     * @param array $columns
     *
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
