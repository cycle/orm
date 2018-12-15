<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\Command\Branch\Split;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Command\Database\Delete;
use Spiral\Cycle\Command\Database\Insert;
use Spiral\Cycle\Command\Database\Update;
use Spiral\Cycle\Context\ConsumerInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Heap\State;
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Zend\Hydrator;

class Mapper extends Selector\Source implements MapperInterface
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    /** @var null|RepositoryInterface */
    protected $repository;

    /** @var ORMInterface */
    protected $orm;

    /** @var string */
    protected $role;

    /** @var Hydrator\HydratorInterface */
    protected $hydrator;

    /** @var array */
    protected $columns;

    /** @var string */
    protected $primaryKey;

    /** @var array */
    protected $children = [];

    /**
     * @param ORMInterface $orm
     * @param string       $role
     */
    public function __construct(ORMInterface $orm, string $role)
    {
        $this->orm = $orm;
        $this->role = $role;
        $this->hydrator = new Hydrator\Reflection();

        $schema = $orm->getSchema();

        parent::__construct(
            $orm->getFactory(),
            $schema->define($role, Schema::DATABASE),
            $schema->define($role, Schema::TABLE)
        );

        $this->columns = $schema->define($role, Schema::COLUMNS);
        $this->primaryKey = $schema->define($role, Schema::PRIMARY_KEY);
        $this->children = $schema->define($role, Schema::CHILDREN) ?? [];
    }

    /**
     * @inheritdoc
     */
    public function getRole(): string
    {
        return $this->orm->getSchema()->define($this->role, Schema::ALIAS);
    }

    /**
     * @inheritdoc
     */
    public function getRepository(): RepositoryInterface
    {
        if (!empty($this->repository)) {
            return $this->repository;
        }

        $selector = new Selector($this->orm, $this->role);
        $selector->scope($this->getScope(self::DEFAULT_SCOPE));

        return $this->repository = new Repository($selector);
    }

    /**
     * @inheritdoc
     */
    public function init(array $data): array
    {
        $class = $this->resolveClass($data);

        // filter data

        return [new $class, $data];
    }

    /**
     * @inheritdoc
     */
    public function hydrate($entity, array $data)
    {
        return $this->hydrator->hydrate($data, $entity);
    }

    /**
     * @inheritdoc
     */
    public function extract($entity): array
    {
        return $this->hydrator->extract($entity);
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
        $delete = new Delete($this->getDatabase(), $this->getTable());
        $node->getState()->setStatus(Node::SCHEDULED_DELETE);
        $node->getState()->decClaim();

        $delete->waitScope($this->primaryKey);
        $node->forward(
            $this->primaryKey,
            $delete,
            $this->primaryKey,
            true,
            ConsumerInterface::SCOPE
        );

        return $delete;
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
        $columns = $this->fetchColumns($entity);

        // sync the state
        $state->setStatus(Node::SCHEDULED_INSERT);
        $state->setData($columns);

        // todo: ID generation on client-side (!)
        unset($columns[$this->primaryKey]);

        $insert = new Insert($this->getDatabase(), $this->getTable(), $columns);
        $insert->forward(Insert::INSERT_ID, $state, $this->primaryKey);

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
        $data = $this->fetchColumns($entity);

        // in a future mapper must support solid states
        $changes = array_diff($data, $state->getData());
        unset($changes[$this->primaryKey]);

        $update = new Update($this->getDatabase(), $this->getTable(), $changes);
        $state->setStatus(Node::SCHEDULED_UPDATE);
        $state->setData($changes);

        // we are trying to update entity without PK right now
        $state->forward(
            $this->primaryKey,
            $update,
            $this->primaryKey,
            true,
            ConsumerInterface::SCOPE
        );

        return $update;
    }

    /**
     * Get entity columns.
     *
     * @param object $entity
     * @return array
     */
    protected function fetchColumns($entity): array
    {
        $columns = array_intersect_key($this->extract($entity), array_flip($this->columns));

        $class = get_class($entity);
        if ($class != $this->role) {
            // inheritance
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                    break;
                }
            }
        }

        return $columns;
    }

    /**
     * Classname to represent entity.
     *
     * @param array $data
     * @return string
     */
    protected function resolveClass(array $data): string
    {
        $class = $this->role;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }
}