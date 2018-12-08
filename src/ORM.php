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
use Spiral\ORM\Command\CarrierInterface;
use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\Control\Nil;
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
    public function getRelationMap($entity): RelationMap
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

    public function fetchOne(string $class, array $scope, bool $load = false)
    {
        if (count($scope) === 1) {
            $p = $class;
            foreach ($scope as $k => $v) {
                $p .= ':' . $k . '.' . $v;
            }

            if ($this->heap->hasPath($p)) {
                return $this->heap->getPath($p);
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
    public function make(string $class, array $data, int $state = Point::NEW)
    {
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        // todo: deal with alias

        if ($state !== Point::NEW) {
            // locate already loaded entity reference
            $entityID = $this->identify($class, $data);

            $path = $class . ':' . $entityID;

            if (!empty($entityID) && $this->heap->hasPath($path)) {
                $existed = $this->heap->getPath($path);

                if ($existed instanceof PromiseInterface) {
                    return $existed;
                }

                // todo: optimize, avoid cyclic initiation ? do i have it?

                // todo: can be promise
                return $this->getMapper($existed)->hydrate(
                    $existed,
                    $this->getRelationMap($existed)->init($this->getHeap()->get($existed), $data)
                );
            }
        }

        $mapper = $this->getMapper($class);

        // init entity class and prepare data, todo: work it out
        list($entity, $filtered) = $mapper->prepare($data);

        // todo: i do not need primary key, but i do need to update paths in mapper
        $state = new Point($state, $filtered, $this->schema->define(get_class($entity), Schema::ALIAS));
        $this->heap->attach($entity, $state, $this->getPaths($entity, $entityID ?? null, $data));

        // hydrate entity with it's data, relations and proxies
        return $mapper->hydrate($entity, $this->getRelationMap($entity)->init($state, $filtered));
    }

    public function queueStore($entity, int $mode = 0): CarrierInterface
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
        $cmd = $this->getRelationMap($entity)->queueRelations(
            $entity,
            $m->extract($entity),
            $state = $this->getHeap()->get($entity),
            $cmd
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
        $this->relmaps = [];
    }

    protected function getPaths($entity, $entityID, array $data): array
    {
        if (is_null($entityID)) {
            return [];
        }

        $keys = $this->schema->define(get_class($entity), Schema::CAPTURE_KEYS) ?? [];

        $paths = [get_class($entity) . ':' . $entityID];
        $paths[] = get_class($entity) . ':' . $this->schema->define(get_class($entity),
                Schema::PRIMARY_KEY) . '.' . $entityID;

        foreach ($keys as $key) {
            if (!empty($data[$key])) {
                $paths[] = get_class($entity) . ':' . $key . '.' . $data[$key];
            }
        }

        return $paths;
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