<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\ORMException;
use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\Reference;
use Cycle\ORM\Registry\EntityFactoryInterface;
use Cycle\ORM\Registry\Implementation\EntityFactory;
use Cycle\ORM\Registry\Implementation\EntityRegistry;
use Cycle\ORM\Registry\Implementation\SourceProvider;
use Cycle\ORM\Registry\Implementation\TypecastProvider;
use Cycle\ORM\Registry\IndexProviderInterface;
use Cycle\ORM\Registry\MapperProviderInterface;
use Cycle\ORM\Registry\RelationProviderInterface;
use Cycle\ORM\Registry\RepositoryProviderInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\Registry\TypecastProviderInterface;
use Cycle\ORM\Select\LoaderInterface;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\CommandGeneratorInterface;
use InvalidArgumentException;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
final class ORM implements ORMInterface
{
    private HeapInterface $heap;

    private CommandGeneratorInterface $commandGenerator;

    private EntityRegistry $entityRegistry;
    private SourceProvider $sourceProvider;
    private TypecastProvider $typecastProvider;
    private EntityFactory $entityFactory;

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
        CommandGeneratorInterface $commandGenerator = null
    ) {
        $this->heap = new Heap();
        $this->commandGenerator = $commandGenerator ?? new CommandGenerator();
        $this->resetRegistry();
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->heap = new Heap();
        $this->resetRegistry();
    }

    public function __debugInfo(): array
    {
        return [
            'schema' => $this->schema,
        ];
    }

    public function resolveRole(string|object $entity): string
    {
        return $this->entityFactory->resolveRole($entity);
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
        return $this->entityFactory->make($role, $data, $status, $typecast);
    }

    public function getCommandGenerator(): CommandGeneratorInterface
    {
        return $this->commandGenerator;
    }

    public function getFactory(): FactoryInterface
    {
        return $this->factory;
    }

    public function getService(
        #[ExpectedValues(values: [
            EntityFactoryInterface::class,
            IndexProviderInterface::class,
            MapperProviderInterface::class,
            RelationProviderInterface::class,
            RepositoryProviderInterface::class,
            SourceProviderInterface::class,
            TypecastProviderInterface::class,
        ])]
        string $class
    ): object
    {
        return match ($class) {
            EntityFactoryInterface::class => $this->entityFactory,
            SourceProviderInterface::class => $this->sourceProvider,
            TypecastProviderInterface::class => $this->typecastProvider,
            IndexProviderInterface::class,
            MapperProviderInterface::class,
            RelationProviderInterface::class,
            RepositoryProviderInterface::class => $this->entityRegistry,
            default => throw new InvalidArgumentException("Undefined service `$class`.")
        };
    }

    public function getSchema(): SchemaInterface
    {
        return $this->schema;
    }

    public function getHeap(): HeapInterface
    {
        return $this->heap;
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
        return $this->sourceProvider->getSource(
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

    public function with(
        ?SchemaInterface $schema = null,
        ?FactoryInterface $factory = null,
        ?HeapInterface $heap = null
    ): ORMInterface {
        $orm = clone $this;

        $orm->heap = $heap ?? $orm->heap;
        $orm->schema = $schema ?? $orm->schema;
        $orm->factory = $factory ?? $orm->factory;

        $orm->resetRegistry();

        return $orm;
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

    private function resetRegistry(): void
    {
        $this->sourceProvider = new SourceProvider($this->factory, $this->schema);
        $this->typecastProvider = new TypecastProvider($this->factory, $this->schema, $this->sourceProvider);
        $this->entityRegistry = new EntityRegistry($this, $this->sourceProvider, $this->schema, $this->factory);
        $this->entityFactory = new EntityFactory(
            $this->heap,
            $this->schema,
            $this->entityRegistry,
            $this->entityRegistry,
            $this->entityRegistry
        );
    }
}
