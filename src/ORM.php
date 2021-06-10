<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\Reference;
use Cycle\ORM\Select\SourceInterface;

use function count;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
final class ORM implements ORMInterface
{
    private FactoryInterface $factory;

    private ?PromiseFactoryInterface $promiseFactory = null;

    private HeapInterface $heap;

    private SchemaInterface $schema;

    /** @var MapperInterface[] */
    private array $mappers = [];

    /** @var RepositoryInterface[] */
    private array $repositories = [];

    /** @var RelationMap[] */
    private array $relMaps = [];

    private array $indexes = [];

    /** @var SourceInterface[] */
    private array $sources = [];

    public function __construct(FactoryInterface $factory, SchemaInterface $schema = null)
    {
        $this->factory = $factory;
        $this->schema = $schema ?? new Schema([]);

        $this->heap = new Heap();
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->heap = new Heap();
        $this->mappers = [];
        $this->relMaps = [];
        $this->indexes = [];
        $this->sources = [];
        $this->repositories = [];
    }

    public function __debugInfo(): array
    {
        return [
            'schema' => $this->schema
        ];
    }

    public function resolveRole($entity): string
    {
        if (is_object($entity)) {
            $node = $this->getHeap()->get($entity);
            if ($node !== null) {
                return $node->getRole();
            }

            $class = get_class($entity);
            if (!$this->schema->defines($class)) {
                throw new ORMException("Unable to resolve role of `$class`");
            }

            $entity = $class;
        }

        return $this->schema->resolveAlias($entity);
    }

    public function get(string $role, array $scope, bool $load = true): ?object
    {
        $role = $this->resolveRole($role);
        $e = $this->heap->find($role, $scope);

        if ($e !== null) {
            return $e;
        }

        if (!$load) {
            return null;
        }

        return $this->getRepository($role)->findOne($scope);
    }

    public function make(string $role, array $data = [], int $node = Node::NEW): ?object
    {
        $m = $this->getMapper($role);

        // unique entity identifier
        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        if (is_array($pk)) {
            $ids = [];
            foreach ($pk as $key) {
                if (!isset($data[$key])) {
                    $ids = null;
                    break;
                }
                $ids[$key] = $data[$key];
            }
        } else {
            $ids = isset($data[$pk]) ? [$pk => $data[$pk]] : null;
        }

        if ($node !== Node::NEW && $ids !== null) {
            $e = $this->heap->find($role, $ids);

            if ($e !== null) {
                $node = $this->heap->get($e);

                // new set of data and relations always overwrite entity state
                return $m->hydrate(
                    $e,
                    $this->getRelationMap($role)->init($node, $data)
                );
            }
        }

        // init entity class and prepared (typecasted) data
        [$e, $prepared] = $m->init($data);

        $nodeObject = new Node($node, $prepared, $m->getRole());

        $this->heap->attach($e, $nodeObject, $this->getIndexes($m->getRole()));

        // hydrate entity with it's data, relations and proxies
        return $m->hydrate(
            $e,
            $this->getRelationMap($role)->init($nodeObject, $prepared)
        );
    }

    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        $orm = clone $this;
        $orm->factory = $factory;

        return $orm;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        $orm = clone $this;
        $orm->schema = $schema;

        return $orm;
    }

    public function getSchema(): SchemaInterface
    {
        if ($this->schema === null) {
            throw new ORMException('ORM is not configured, schema is missing');
        }

        return $this->schema;
    }

    public function withHeap(HeapInterface $heap): ORMInterface
    {
        $orm = clone $this;
        $orm->heap = $heap;

        return $orm;
    }

    public function getHeap(): HeapInterface
    {
        return $this->heap;
    }

    public function getMapper($entity): MapperInterface
    {
        $role = $this->resolveRole($entity);
        if (isset($this->mappers[$role])) {
            return $this->mappers[$role];
        }

        return $this->mappers[$role] = $this->factory->mapper($this, $this->schema, $role);
    }

    public function getRepository($entity): RepositoryInterface
    {
        $role = $this->resolveRole($entity);
        if (isset($this->repositories[$role])) {
            return $this->repositories[$role];
        }

        $select = null;

        if ($this->schema->define($role, Schema::TABLE) !== null) {
            $select = new Select($this, $role);
            $select->constrain($this->getSource($role)->getConstrain());
        }

        return $this->repositories[$role] = $this->factory->repository($this, $this->schema, $role, $select);
    }

    public function getSource(string $role): SourceInterface
    {
        if (isset($this->sources[$role])) {
            return $this->sources[$role];
        }

        return $this->sources[$role] = $this->factory->source($this, $this->schema, $role);
    }

    /**
     * Overlay existing promise factory.
     */
    public function withPromiseFactory(PromiseFactoryInterface $promiseFactory = null): self
    {
        $orm = clone $this;
        $orm->promiseFactory = $promiseFactory;

        return $orm;
    }

    /**
     * Returns references by default.
     */
    public function promise(string $role, array $scope): object
    {
        if (count($scope) === 1) {
            $e = $this->heap->find($role, $scope);
            if ($e !== null) {
                return $e;
            }
        }

        if ($this->promiseFactory !== null) {
            return $this->promiseFactory->promise($this, $role, $scope);
        }

        return new Reference($role, $scope);
    }

    /**
     * Get list of keys entity must be indexed in a Heap by.
     */
    private function getIndexes(string $role): array
    {
        if (isset($this->indexes[$role])) {
            return $this->indexes[$role];
        }

        $pk = $this->schema->define($role, Schema::PRIMARY_KEY);
        $keys = $this->schema->define($role, Schema::FIND_BY_KEYS) ?? [];

        return $this->indexes[$role] = array_unique(array_merge([$pk], $keys));
    }

    /**
     * Get relation map associated with the given class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        $role = $this->resolveRole($entity);
        if (isset($this->relMaps[$role])) {
            return $this->relMaps[$role];
        }

        $outerRelations = $this->schema->getOuterRelations($role);
        $innerRelations = $this->schema->getInnerRelations($role);
        $relations = [];

        foreach ($innerRelations as $relName => $relSchema) {
            $relations[$relName] = $this->factory->relation($this, $this->schema, $role, $relName);
        }
        $map = new RelationMap($relations, $outerRelations);
        $this->relMaps[$role] = $map;
        return $map;
    }
}
