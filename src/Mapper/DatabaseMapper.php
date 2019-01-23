<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\Column\Typecaster;
use Spiral\Cycle\Column\TypecasterInterface;
use Spiral\Cycle\Command\Branch\Split;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\Database\Delete;
use Spiral\Cycle\Command\Database\Insert;
use Spiral\Cycle\Command\Database\Update;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Exception\MapperException;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Heap\State;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Select;

/**
 * Provides basic capabilities to work with entities persisted in SQL databases.
 */
abstract class DatabaseMapper implements MapperInterface
{
    /** @var Select\SourceInterface */
    protected $source;

    /** @var null|RepositoryInterface */
    protected $repository;

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

    /** @var TypecasterInterface|null */
    protected $typecast;

    /**
     * @param ORMInterface             $orm
     * @param string                   $role
     * @param TypecasterInterface|null $typecast
     */
    public function __construct(ORMInterface $orm, string $role, TypecasterInterface $typecast = null)
    {
        if (!$orm instanceof Select\SourceFactoryInterface) {
            throw new MapperException("Source factory is missing");
        }

        $this->orm = $orm;
        $this->role = $role;

        $this->source = $orm->getSource($role);
        $this->columns = $orm->getSchema()->define($role, Schema::COLUMNS);
        $this->primaryKey = $orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        $this->primaryColumn = $this->columns[$this->primaryKey] ?? $this->primaryKey;

        if (!is_null($rules = $orm->getSchema()->define($role, Schema::TYPECAST))) {
            $typecast = $typecast ?? new Typecaster();
            $this->typecast = $typecast->withRules($rules);
        }

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
    public function getRepository(): RepositoryInterface
    {
        if (!empty($this->repository)) {
            return $this->repository;
        }

        $selector = new Select($this->orm, $this->role);
        $selector->constrain($this->source->getConstrain(Select\SourceInterface::DEFAULT_CONSTRAIN));

        return $this->repository = new Repository($selector);
    }

    /**
     * @inheritdoc
     */
    public function queueStore($entity, Node $node): ContextCarrierInterface
    {
        if ($node->getStatus() == Node::NEW) {
            $cmd = $this->queueCreate($entity, $node->getState());
            $node->getState()->setCommand($cmd);

            return $cmd;
        }

        $lastCommand = $node->getState()->getCommand();
        if (empty($lastCommand)) {
            return $this->queueUpdate($entity, $node->getState());
        }

        if ($lastCommand instanceof Split || $lastCommand instanceof Update) {
            return $lastCommand;
        }

        // in cases where we have to update new entity we can merge two commands into one
        $split = new Split($lastCommand, $this->queueUpdate($entity, $node->getState()));
        $node->getState()->setCommand($split);

        return $split;
    }

    /**
     * @inheritdoc
     */
    public function queueDelete($entity, Node $node): CommandInterface
    {
        $delete = new Delete($this->source->getDatabase(), $this->source->getTable());
        $node->getState()->setStatus(Node::SCHEDULED_DELETE);
        $node->getState()->decClaim();

        $delete->waitScope($this->primaryColumn);
        $node->forward(
            $this->primaryKey,
            $delete,
            $this->primaryColumn,
            true,
            ConsumerInterface::SCOPE
        );

        return $delete;
    }

    /**
     * @param array $data
     * @return array
     */
    protected function prepareData(array $data): array
    {
        if ($this->typecast !== null) {
            return $this->typecast->cast($data, $this->source->getDatabase());
        }

        return $data;
    }

    /**
     * Generate command or chain of commands needed to insert entity into the database.
     *
     * @param object $entity
     * @param State  $state
     * @return ContextCarrierInterface
     */
    protected function queueCreate($entity, State $state): ContextCarrierInterface
    {
        $columns = $this->fetchFields($entity);

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);
        $state->setData($columns);

        $columns[$this->primaryKey] = $this->generatePrimaryKey();
        if (is_null($columns[$this->primaryKey])) {
            unset($columns[$this->primaryKey]);
        }

        $insert = new Insert(
            $this->source->getDatabase(),
            $this->source->getTable(),
            $this->mapColumns($columns)
        );

        if (!array_key_exists($this->primaryKey, $columns)) {
            $insert->forward(Insert::INSERT_ID, $state, $this->primaryKey);
        } else {
            $insert->forward($this->primaryKey, $state, $this->primaryKey);
        }

        return $insert;
    }

    /**
     * Generate command or chain of commands needed to update entity in the database.
     *
     * @param object $entity
     * @param State  $state
     * @return ContextCarrierInterface
     */
    protected function queueUpdate($entity, State $state): ContextCarrierInterface
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
     * Generate next sequential entity ID. Return null to use autoincrement value.
     *
     * @return mixed|null
     */
    protected function generatePrimaryKey()
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