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
    public function make(string $class, array $data, int $state = State::NEW)
    {
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }

        // todo: deal with alias

        if ($state !== State::NEW) {
            // locate already loaded entity reference
            $entityID = $this->identify($class, $data);

            if (!empty($entityID) && $this->heap->hasPath($class, $entityID)) {
                // entity already known and loaded
                return $this->heap->getPath($class, $entityID);
            }
        }

        $mapper = $this->getMapper($class);

        $state = new State($entityID ?? null, $state, $data);
        $entity = $mapper->init($mapper->entityClass($data));

        if (!empty($entityID)) {
            $this->heap->attach($entity, $state);
        }

        // hydrate entity with it's data and relations
        return $mapper->hydrate(
            $entity,
            $this->getRelationMap($entity)->init($state, $data)
        );
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->mappers = [];
        $this->relmaps = [];
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