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
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\Reflection;

class Mapper extends Source implements MapperInterface
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    /** @var null|RepositoryInterface */
    private $repository;

    /** @var string */
    private $role;


    protected $primaryKey;

    protected $children;

    protected $columns;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    public function __construct(ORMInterface $orm, string $role)
    {
        parent::__construct(
            $orm,
            $orm->getSchema()->define($role, Schema::DATABASE),
            $orm->getSchema()->define($role, Schema::TABLE)
        );

        $this->role = $role;

        // todo: make it better
        $this->columns = $this->orm->getSchema()->define($role, Schema::COLUMNS);
        $this->primaryKey = $this->orm->getSchema()->define($role, Schema::PRIMARY_KEY);
        $this->children = $this->orm->getSchema()->define($role, Schema::CHILDREN) ?? [];

        $this->hydrator = new Reflection();
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
        $class = $this->entityClass($data);

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

        // when update command is required for non created entity
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

        // todo: better?
        $class = get_class($entity);
        if ($class != $this->role) {
            // possibly children
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                }
            }
        }

        return $columns;
    }

    // todo: polish
    protected function entityClass(array $data): string
    {
        $class = $this->role;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }
}