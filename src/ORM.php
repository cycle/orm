<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\ORM\Command\Branch\Nil;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Config\RelationConfig;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
class ORM implements ORMInterface
{
    // Memory section to store ORM schema.
    protected const MEMORY = 'orm.schema';

    /** @var HeapInterface */
    private $heap;

    /** @var DatabaseManager */
    private $dbal;

    /** @var FactoryInterface */
    private $factory;

    /** @var SchemaInterface */
    private $schema;

    /** @var MapperInterface[] */
    private $mappers = [];

    /** @var RelationMap[] */
    private $relmaps = [];

    /**
     * @param DatabaseManager       $dbal
     * @param FactoryInterface|null $factory
     */
    public function __construct(DatabaseManager $dbal, FactoryInterface $factory = null)
    {
        $this->heap = new Heap();
        $this->dbal = $dbal;
        $this->factory = $factory ?? new Factory(RelationConfig::createDefault());
    }

    public function getDBAL(): DatabaseManager
    {
        return $this->dbal;
    }

    /**
     * @inheritdoc
     */
    public function getDatabase($entity): DatabaseInterface
    {
        $entity = $this->resolveClass($entity);

        return $this->dbal->database(
            $this->getSchema()->define($entity, Schema::DATABASE)
        );
    }

    /**
     * @inheritdoc
     */
    public function getMapper($entity): MapperInterface
    {
        $entity = $this->resolveClass($entity);

        if (isset($this->mappers[$entity])) {
            return $this->mappers[$entity];
        }

        return $this->mappers[$entity] = $this->getFactory()->mapper($entity);
    }

    /**
     * Get relation map associated with the given class.
     *
     * @param string $entity
     * @return RelationMap
     */
    public function getRelmap($entity): RelationMap
    {
        $entity = is_object($entity) ? get_class($entity) : $entity;

        if (isset($this->relmaps[$entity])) {
            return $this->relmaps[$entity];
        }

        $relations = [];

        $names = array_keys($this->getSchema()->define($entity, Schema::RELATIONS));
        foreach ($names as $relation) {
            $relations[$relation] = $this->getFactory()->relation($entity, $relation);
        }

        return $this->relmaps[$entity] = new RelationMap($this, $relations);
    }

    public function get(string $class, array $scope, bool $load = false)
    {
        foreach ($scope as $k => $v) {
            if (!empty($e = $this->heap->find($class, $k, $v))) {
                return $e;
            }
        }

        if ($load) {
            return $this->getMapper($class)->getRepository()->findOne($scope);
        }

        return null;
    }

    /**
     * @inheritdoc
     */
    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $orm = clone $this;
        $orm->schema = $schema;
        $orm->factory = $orm->factory->withConfigured($orm, $orm->schema);

        return $orm;
    }

    /**
     * @inheritdoc
     */
    public function getSchema(): SchemaInterface
    {
        if (empty($this->schema)) {
            $this->schema = $this->loadSchema();
            $this->factory = $this->factory->withConfigured($this, $this->schema);
        }

        return $this->schema;
    }

    /**
     * @inheritdoc
     */
    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        $orm = clone $this;
        $orm->factory = $factory->withConfigured($orm, $orm->schema);

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
    public function withHeap(HeapInterface $heap): ORMInterface
    {
        $orm = clone $this;
        $orm->heap = $heap;
        $orm->factory = $orm->factory->withConfigured($orm, $orm->schema);

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
    public function make(string $role, array $data, int $node = Node::NEW)
    {
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $id = $data[$pk] ?? null;

        $mapper = $this->getMapper($role);

        if ($node !== Node::NEW && !empty($id)) {
            if (!empty($e = $this->heap->find($role, $pk, $id))) {
                // entity already been loaded, let's update it's relations with new context
                return $mapper->hydrate($e, $this->getRelmap($e)->init($this->getHeap()->get($e), $data));
            }
        }

        // init entity class and prepare data, todo: work it out
        list($e, $filtered) = $mapper->init($data);

        // todo: fix it (!)
        $node = new Node($node, $filtered, $this->schema->define(get_class($e), Schema::ALIAS));

        $this->heap->attach($e, $node, $this->getIndexes($e));

        // hydrate entity with it's data, relations and proxies
        return $mapper->hydrate($e, $this->getRelmap($e)->init($node, $filtered));
    }

    public function queueStore($entity, int $mode = 0): ContextCarrierInterface
    {
        // todo: NICE?

        if ($entity instanceof PromiseInterface) {
            // todo: i don't like you
            return new Nil();
        }

        $m = $this->getMapper($entity);
        $cmd = $m->queueStore($entity);
        // TODO: RESET HANDLERS

        // todo: optimize it
        $cmd = $this->getRelmap($entity)->queueRelations(
            $cmd,
            $entity,
            $state = $this->getHeap()->get($entity),
            $m->extract($entity)
        );

        return $cmd;
    }

    public function queueDelete($entity, int $mode = 0): CommandInterface
    {
        if ($entity instanceof PromiseInterface) {
            return new Nil();
        }

        return $this->getMapper($entity)->queueDelete($entity);
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->typecasts = [];
        $this->relmaps = [];
    }

    protected function getIndexes($entity): array
    {
        $pk = $this->schema->define(get_class($entity), Schema::PRIMARY_KEY);
        $keys = $this->schema->define(get_class($entity), Schema::CAPTURE_KEYS) ?? [];

        return array_merge([$pk], $keys);
    }

    /**
     * Return value to uniquely identify given entity data. Most likely PrimaryKey value.
     *
     * @param string $class
     * @param array  $data
     * @return string|int|null
     */
    protected function identify(string $class, array $data)
    {
        $pk = $this->getSchema()->define($class, Schema::PRIMARY_KEY);

        if (isset($data[$pk])) {
            return $data[$pk];
        }

        return null;
    }

    protected function getInitPaths(string $class, array $data): array
    {

    }

    protected function resolveClass($entity): string
    {
        //if ($entity instanceof PromiseInterface) {
        // fallback to the promise class
        //}

        $entity = is_object($entity) ? get_class($entity) : $entity;

        return $this->getSchema()->define($entity, Schema::EXTENDS) ?? $entity;
    }

    protected function loadSchema(): SchemaInterface
    {
        return new Schema([
            // hahahaha
        ]);
    }
}