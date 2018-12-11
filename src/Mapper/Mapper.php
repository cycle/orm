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
use Spiral\Cycle\ORMInterface;
use Spiral\Cycle\Schema;
use Spiral\Cycle\Selector;
use Spiral\Database\DatabaseInterface;
use Zend\Hydrator\HydratorInterface;
use Zend\Hydrator\Reflection;

class Mapper implements MapperInterface, Selector\SourceInterface
{
    // system column to store entity type
    public const ENTITY_TYPE = '_type';

    protected $orm;

    protected $role;

    protected $table;

    protected $primaryKey;

    protected $children;

    protected $columns;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    public function __construct(ORMInterface $orm, string $class)
    {
        $this->orm = $orm;
        $this->role = $class;

        // todo: mass export
        $this->columns = $this->orm->getSchema()->define($class, Schema::COLUMNS);
        $this->table = $this->orm->getSchema()->define($class, Schema::TABLE);
        $this->primaryKey = $this->orm->getSchema()->define($class, Schema::PRIMARY_KEY);
        $this->children = $this->orm->getSchema()->define($class, Schema::CHILDREN) ?? [];
        $this->hydrator = new Reflection();
    }

    /**
     * @inheritdoc
     */
    public function getRole(): string
    {
        return $this->orm->getSchema()->define($this->role, Schema::ALIAS);
    }


    public function entityClass(array $data): string
    {
        $class = $this->role;
        if (!empty($this->children) && !empty($data[self::ENTITY_TYPE])) {
            $class = $this->children[$data[self::ENTITY_TYPE]] ?? $class;
        }

        return $class;
    }

    public function getDatabase(): DatabaseInterface
    {
        return $this->orm->getDBAL()->database($this->orm->getSchema()->define($this->role, Schema::DATABASE));
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getSelector(): Selector
    {
        $selector = new Selector($this->orm, $this->role);
        if (!empty($scope = $this->getScope(self::DEFAULT_SCOPE))) {
            $selector = $selector->withScope($scope);
        }

        return $selector;
    }

    public function getScope(string $name = self::DEFAULT_SCOPE): ?Selector\ScopeInterface
    {
        return null;
    }

    public function hydrate($entity, array $data)
    {
        return $this->hydrator->hydrate($data, $entity);
    }

    public function extract($entity): array
    {
        return $this->hydrator->extract($entity);
    }


    public function getRepository(string $class = null): RepositoryInterface
    {
        // todo: child class select
        return new Repository(new Selector($this->orm, $class ?? $this->role));
    }


    public function init(array $data): array
    {
        $class = $this->entityClass($data);

        return [new $class, $data];
    }

    // todo: need state as INPUT!!!!
    public function queueStore(Node $node, $entity): ContextCarrierInterface
    {
        //        /** @var Node $point */
        //        $point = $this->orm->getHeap()->get($entity);
        //        if (is_null($point)) {
        //            // todo: do we need to track PK?
        //            $point = new Node(
        //                Node::NEW,
        //                [],
        //                $this->orm->getSchema()->define(get_class($entity), Schema::ALIAS)
        //            );
        //            $this->orm->getHeap()->attach($entity, $point);
        //        }

        if ($node == null || $node->getStatus() == Node::NEW) {
            $cmd = $this->queueCreate($entity, $node);
            $node->getState()->setCommand($cmd);

            return $cmd;
        }

        $lastCommand = $node->getState()->getCommand();

        if (empty($lastCommand)) {
            // todo: check multiple update commands working within the split (!)
            return $this->queueUpdate($entity, $node);
        }

        if ($lastCommand instanceof Split) {
            return $lastCommand;
        }

        // todo: do i like it?
        $split = new Split($lastCommand, $this->queueUpdate($entity, $node));
        $node->getState()->setCommand($split);

        return $split;
    }

    public function queueDelete(Node $node, $entity): CommandInterface
    {
        //        $node = $this->orm->getHeap()->get($entity);
        //        if ($node == null) {
        //            // todo: this should not happen, todo: need nullable delete
        //            return new Nil();
        //        }

        // todo: delete relations as well

        $delete = new Delete($this->getDatabase(), $this->table);

        $node->setStatus(Node::SCHEDULED_DELETE);
        $node->getState()->decClaim();

        $delete->waitScope($this->primaryKey);
        $node->forward($this->primaryKey, $delete, $this->primaryKey, true, ConsumerInterface::SCOPE);

        // todo: this must be changed (CORRECT?) BUT HOW?
        //  $delete->onComplete(function () use ($entity) {
        //      $this->orm->getHeap()->detach($entity);
        //  });

        return $delete;
    }

    protected function getColumns($entity): array
    {
        return array_intersect_key($this->extract($entity), array_flip($this->columns));
    }

    // todo: state must not be null
    protected function queueCreate($entity, Node &$state = null): ContextCarrierInterface
    {
        $columns = $this->getColumns($entity);

        $class = get_class($entity);
        if ($class != $this->role) {
            // possibly children
            foreach ($this->children as $alias => $childClass) {
                if ($childClass == $class) {
                    $columns[self::ENTITY_TYPE] = $alias;
                }
            }

            // todo: what is that?
            // todo: exception
        }

        // to the point
        $state->setData($columns);

        $state->setStatus(Node::SCHEDULED_INSERT);

        // todo: this is questionable (what if ID not autogenerated)
        unset($columns[$this->primaryKey]);

        $insert = new Insert($this->getDatabase(), $this->table, $columns);

        $insert->forward(Insert::INSERT_ID, $state, $this->primaryKey);

        return $insert;
    }

    protected function queueUpdate($entity, Node $state): ContextCarrierInterface
    {
        $eData = $this->getColumns($entity);
        $oData = $state->getData();
        $cData = array_diff($eData, $oData);

        // todo: pack changes (???) depends on mode (USE ALL FOR NOW)

        // todo: this part is weird
        unset($cData[$this->primaryKey]);

        $update = new Update($this->getDatabase(), $this->table, $cData);
        $state->setStatus(Node::SCHEDULED_UPDATE);
        $state->setData($cData);

        // todo: scope prefix (call immediatelly?)
        $state->forward($this->primaryKey, $update, $this->primaryKey, true, ConsumerInterface::SCOPE);

        return $update;
    }
}