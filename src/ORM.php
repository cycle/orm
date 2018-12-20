<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle;

use Spiral\Cycle\Command\Branch\Nil;
use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Exception\ORMException;
use Spiral\Cycle\Heap\Heap;
use Spiral\Cycle\Heap\HeapInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\MapperInterface;
use Spiral\Cycle\Promise\PromiseInterface;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
class ORM implements ORMInterface
{
    // Memory section to store ORM schema.
    protected const MEMORY = 'orm.schema';

    /** @var FactoryInterface */
    private $factory;

    /** @var HeapInterface */
    private $heap;

    /** @var SchemaInterface */
    private $schema;

    /** @var MapperInterface[] */
    private $mappers = [];

    /** @var RelationMap[] */
    private $relmaps = [];

    /** @var array */
    private $indexes = [];

    /**
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->heap = new Heap();
    }

    /**
     * @inheritdoc
     */
    public function get(string $role, array $scope, bool $load = false)
    {
        foreach ($scope as $k => $v) {
            if (!empty($e = $this->heap->find($role, $k, $v))) {
                return $e;
            }
        }

        if ($load) {
            $role = $this->schema->getClass($role) ?? $role;
            return $this->getMapper($role)->getRepository()->findOne($scope);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function make(string $role, array $data, int $node = Node::NEW)
    {
        $m = $this->getMapper($role);

        // unique entity identifier
        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $id = $data[$pk] ?? null;

        if ($node !== Node::NEW && !empty($id)) {
            if (!empty($e = $this->heap->find($role, $pk, $id))) {
                $node = $this->getHeap()->get($e);

                // entity already been loaded, let's update it's relations with new context
                return $m->hydrate($e, $this->getRelmap($role)->init($node, $data));
            }
        }

        // init entity class and prepared (typecasted) data
        list($e, $prepared) = $m->init($data);

        $node = new Node($node, $prepared, $m->getRole());

        $this->heap->attach($e, $node, $this->getIndexes($m->getRole()));

        // hydrate entity with it's data, relations and proxies
        return $m->hydrate($e, $this->getRelmap($role)->init($node, $prepared));
    }

    /**
     * @inheritdoc
     */
    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        $orm = clone $this;
        $orm->factory = $factory->withContext($orm, $orm->schema);

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    /**
     * @inheritdoc
     */
    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $orm = clone $this;
        $orm->schema = $schema;
        $orm->factory = $orm->factory->withContext($orm, $orm->schema);

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): SchemaInterface
    {
        if (empty($this->schema)) {
            $this->schema = $this->loadSchema();
            $this->factory = $this->factory->withContext($this, $this->schema);
        }

        return $this->schema;
    }

    /**
     * @inheritdoc
     */
    public function withHeap(HeapInterface $heap): ORMInterface
    {
        $orm = clone $this;
        $orm->heap = $heap;
        $orm->factory = $orm->factory->withContext($orm, $orm->schema);

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getHeap(): HeapInterface
    {
        return $this->heap;
    }

    /**
     * @inheritdoc
     */
    public function getMapper($entity): MapperInterface
    {
        if (is_string($entity)) {
            $entity = $this->schema->getClass($entity) ?? $entity;
        }

        // todo: resolve role
        $entity = $this->getRole($entity);

        if (isset($this->mappers[$entity])) {
            return $this->mappers[$entity];
        }

        return $this->mappers[$entity] = $this->factory->mapper($entity);
    }

    /**
     * @inheritdoc
     */
    public function queueStore($entity, int $mode = TransactionInterface::MODE_CASCADE): ContextCarrierInterface
    {
        if ($entity instanceof PromiseInterface) {
            // we do not expect to store promises
            return new Nil();
        }

        $m = $this->getMapper($entity);

        $node = $this->heap->get($entity);
        if (is_null($node)) {
            // automatic entity registration
            $node = new Node(Node::NEW, [], $m->getRole());
            $this->heap->attach($entity, $node);
        }

        $cmd = $m->queueStore($entity, $node);
        if (!$mode == TransactionInterface::MODE_CASCADE) {
            return $cmd;
        }

        // generate set of commands required to store entity relations
        return $this->getRelmap($node->getRole())->queueRelations(
            $cmd,
            $entity,
            $node,
            $m->extract($entity)
        );
    }

    /**
     * @inheritdoc
     */
    public function queueDelete($entity, int $mode = TransactionInterface::MODE_CASCADE): CommandInterface
    {
        $node = $this->heap->get($entity);
        if ($entity instanceof PromiseInterface || is_null($node)) {
            // nothing to do
            return new Nil();
        }

        return $this->getMapper($node->getRole())->queueDelete($entity, $node);
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->relmaps = [];
        $this->indexes = [];
    }

    /**
     * Get list of keys entity must be indexed in a Heap by.
     *
     * @param string $role
     * @return array
     */
    protected function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $keys = $this->schema->define($role, Schema::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_merge([$pk], $keys);
    }

    /**
     * Get the role of a given entity.
     *
     * @param object|string $entity
     * @return string
     */
    protected function getRole($entity): string
    {
        $class = is_object($entity) ? get_class($entity) : $entity;

        if ($this->schema->defines($class)) {
            // check if class is being inherited
            return $this->schema->define($class, Schema::EXTENDS) ?? $class;
        }

        // use role associated with the node (for roles without specific class)
        if (!empty($node = $this->heap->get($entity))) {
            return $node->getRole();
        }

        throw new ORMException("Undefined class {$class}");
    }

    /**
     * Get relation map associated with the given class.
     *
     * @param string $entity
     * @return RelationMap
     */
    protected function getRelmap($entity): RelationMap
    {
        $entity = is_object($entity) ? get_class($entity) : $entity;

        if (isset($this->relmaps[$entity])) {
            return $this->relmaps[$entity];
        }

        $relations = [];

        $names = array_keys($this->schema->define($entity, Schema::RELATIONS));
        foreach ($names as $relation) {
            $relations[$relation] = $this->factory->relation($entity, $relation);
        }

        return $this->relmaps[$entity] = new RelationMap($this, $relations);
    }

    protected function loadSchema(): SchemaInterface
    {
        return new Schema([
            // hahahaha
        ]);
    }
}