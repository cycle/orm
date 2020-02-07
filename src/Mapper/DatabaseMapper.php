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
            if (!is_numeric($name)) {
                $this->fields[] = $name;
            } else {
                $this->fields[] = $column;
            }
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

        if (!array_key_exists($this->primaryKey, $columns)) {
            $insert->forward(Insert::INSERT_ID, $state, $this->primaryKey);
        } else {
            $insert->forward($this->primaryKey, $state, $this->primaryKey);
        }

        return $insert;
    }

    /**
     * @inheritdoc
     */
    public function queueUpdate($entity, Node $node, State $state): ContextCarrierInterface
    {
        $data = $this->fetchFields($entity);

        // in a future mapper must support solid states
        $changes = array_udiff_assoc($data, $state->getData(), [static::class, 'compare']);
        unset($changes[$this->primaryKey]);

        $changedColumns = $this->mapColumns($changes);

        $update = new Update($this->source->getDatabase(), $this->source->getTable(), $changedColumns);
        $state->setStatus(Node::SCHEDULED_UPDATE);
        $state->setData($changes);

        // we are trying to update entity without PK right now
        $state->forward(
            $this->primaryKey,
            $update,
            $this->primaryColumn,
            true,
            ConsumerInterface::SCOPE
        );

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
     * @return array
     */
    abstract protected function fetchFields($entity): array;

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
            if (array_key_exists($column, $this->columns)) {
                $result[$this->columns[$column]] = $value;
            } else {
                $result[$column] = $value;
            }
        }

        return $result;
    }

    /**
     * @param mixed $a
     * @param mixed $b
     * @return int
     */
    protected static function compare($a, $b): int
    {
        if ($a == $b) {
            return 0;
        }

        return ($a > $b) ? 1 : -1;
    }
}
