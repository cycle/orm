<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\CommandGeneratorInterface;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
final class ORM implements ORMInterface
{
    private HeapInterface $heap;

    private CommandGeneratorInterface $commandGenerator;

    private EntityRegistryInterface $entityRegistry;

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
        CommandGeneratorInterface $commandGenerator = null
    ) {
        $this->heap = new Heap();
        $this->commandGenerator = $commandGenerator ?? new CommandGenerator();
        $this->resetEntityRegister();
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->heap = new Heap();
        $this->resetEntityRegister();
    }

    public function __debugInfo(): array
    {
        return [
            'schema' => $this->schema,
        ];
    }

    public function resolveRole(string|object $entity): string
    {
        if (\is_object($entity)) {
            $node = $this->getHeap()->get($entity);
            if ($node !== null) {
                return $node->getRole();
            }

            $class = $entity::class;
            if (!$this->schema->defines($class)) {
                // todo: redesign
                // temporary solution for proxy objects
                $parentClass = get_parent_class($entity);
                if ($parentClass === false
                    || substr($class, -6) !== chr(160) . 'Proxy'
                    || !$this->schema->defines($parentClass)
                ) {
                    throw new ORMException("Unable to resolve role of `$class`.");
                }
                $class = $parentClass;
            }

            $entity = $class;
        }

        return $this->schema->resolveAlias($entity) ?? throw new ORMException("Unable to resolve role `$entity`.");
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

    public function make(string $role, array $data = [], int $status = Node::NEW, bool $typecast = false): object
    {
        $role = $data[LoaderInterface::ROLE_KEY] ?? $role;
        unset($data[LoaderInterface::ROLE_KEY]);
        // Resolved role
        $rRole = $this->resolveRole($role);
        $relMap = $this->entityRegistry->getRelationMap($rRole);
        $mapper = $this->entityRegistry->getMapper($rRole);

        $castedData = $typecast ? $mapper->cast($data) : $data;

        if ($status !== Node::NEW) {
            // unique entity identifier
            $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
            if (\is_array($pk)) {
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

            if ($ids !== null) {
                $e = $this->heap->find($rRole, $ids);

                if ($e !== null) {
                    $node = $this->heap->get($e);
                    \assert($node !== null);

                    return $mapper->hydrate($e, $relMap->init($node, $castedData));
                }
            }
        }

        $node = new Node($status, $castedData, $rRole, $data);
        $e = $mapper->init($data, $role);

        /** Entity should be attached before {@see RelationMap::init()} running */
        $this->heap->attach($e, $node, $this->entityRegistry->getIndexes($rRole));

        return $mapper->hydrate($e, $relMap->init($node, $castedData));
    }

    public function getCommandGenerator(): CommandGeneratorInterface
    {
        return $this->commandGenerator;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function getHeap(): HeapInterface
    {
        return $this->heap;
    }

    public function getEntityRegistry(): EntityRegistryInterface
    {
        return $this->entityRegistry;
    }

    public function getMapper(string|object $entity): MapperInterface
    {
        return $this->entityRegistry->getMapper(
            $this->resolveRole($entity)
        );
    }

    public function getRepository(string|object $entity): RepositoryInterface
    {
        return $this->entityRegistry->getRepository(
            $this->resolveRole($entity)
        );
    }

    public function getSource(string $entity): SourceInterface
    {
        return $this->entityRegistry->getSource(
            $this->resolveRole($entity)
        );
    }

    public function promise(string $role, array $scope): object
    {
        if (\count($scope) === 1) {
            $e = $this->heap->find($role, $scope);
            if ($e !== null) {
                return $e;
            }
        }

        return new Reference($role, $scope);
    }

    public function getIndexes(string $entity): array
    {
        return $this->entityRegistry->getIndexes(
            $this->resolveRole($entity)
        );
    }

    /**
     * Get relation map associated with the given class.
     *
     * todo: the ORMInterface hasn't this method
     */
    public function getRelationMap(string $entity): RelationMap
    {
        return $this->entityRegistry->getRelationMap(
            $this->resolveRole($entity)
        );
    }

    /**
     * @deprecated since Cycle ORM v1.8, this method will be removed in future releases.
     * Use method {@see with} instead.
     */
    public function withSchema(SchemaInterface $schema): ORMInterface
    {
        return $this->with($schema);
    }

    /**
     * @deprecated since Cycle ORM v1.8, this method will be removed in future releases.
     * Use method {@see with} instead.
     */
    public function withFactory(FactoryInterface $factory): ORMInterface
    {
        return $this->with(factory: $factory);
    }

    /**
     * @deprecated since Cycle ORM v1.8, this method will be removed in future releases.
     * Use method {@see with} instead.
     */
    public function withHeap(HeapInterface $heap): ORMInterface
    {
        return $this->with(heap: $heap);
    }

    public function with(
        ?SchemaInterface $schema = null,
        ?FactoryInterface $factory = null,
        ?HeapInterface $heap = null
    ): ORMInterface {
        $orm = clone $this;

        $orm->heap = $heap ?? $orm->heap;

        if ($schema !== null || $factory !== null) {
            $orm->schema = $schema ?? $orm->schema;
            $orm->factory = $factory ?? $orm->factory;

            $orm->resetEntityRegister();
        }

        return $orm;
    }

    private function resetEntityRegister(): void
    {
        $this->entityRegistry = new EntityRegistry($this, $this->schema, $this->factory);
    }
}
