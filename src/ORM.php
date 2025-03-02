<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Heap;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Select\SourceInterface;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\Implementation\EntityFactory;
use Cycle\ORM\Service\Implementation\EntityProvider;
use Cycle\ORM\Service\Implementation\IndexProvider;
use Cycle\ORM\Service\Implementation\MapperProvider;
use Cycle\ORM\Service\Implementation\RelationProvider;
use Cycle\ORM\Service\Implementation\RepositoryProvider;
use Cycle\ORM\Service\Implementation\RoleResolver;
use Cycle\ORM\Service\Implementation\SourceProvider;
use Cycle\ORM\Service\Implementation\TypecastProvider;
use Cycle\ORM\Service\IndexProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Service\RepositoryProviderInterface;
use Cycle\ORM\Service\RoleResolverInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Service\TypecastProviderInterface;
use Cycle\ORM\Transaction\CommandGenerator;
use Cycle\ORM\Transaction\CommandGeneratorInterface;
use JetBrains\PhpStorm\ExpectedValues;

/**
 * Central class ORM, provides access to various pieces of the system and manages schema state.
 */
final class ORM implements ORMInterface
{
    private HeapInterface $heap;
    private CommandGeneratorInterface $commandGenerator;
    private RelationProvider $relationProvider;
    private SourceProvider $sourceProvider;
    private TypecastProvider $typecastProvider;
    private EntityFactoryInterface $entityFactory;
    private IndexProvider $indexProvider;
    private MapperProvider $mapperProvider;
    private RepositoryProvider $repositoryProvider;
    private EntityProvider $entityProvider;
    private RoleResolverInterface $roleResolver;

    public function __construct(
        private FactoryInterface $factory,
        private SchemaInterface $schema,
        ?CommandGeneratorInterface $commandGenerator = null,
        ?HeapInterface $heap = null,
    ) {
        $this->heap = $heap ?? new Heap();
        $this->commandGenerator = $commandGenerator ?? new CommandGenerator();
        $this->resetRegistry();
    }

    public function resolveRole(string|object $entity): string
    {
        return $this->roleResolver->resolveRole($entity);
    }

    public function get(string $role, array $scope, bool $load = true): ?object
    {
        $role = $this->resolveRole($role);
        return $this->entityProvider->get($role, $scope, $load);
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
        string $class,
    ): object {
        return match ($class) {
            EntityFactoryInterface::class => $this->entityFactory,
            EntityProviderInterface::class => $this->entityProvider,
            SourceProviderInterface::class => $this->sourceProvider,
            TypecastProviderInterface::class => $this->typecastProvider,
            IndexProviderInterface::class => $this->indexProvider,
            MapperProviderInterface::class => $this->mapperProvider,
            RelationProviderInterface::class => $this->relationProvider,
            RepositoryProviderInterface::class => $this->repositoryProvider,
            default => throw new \InvalidArgumentException("Undefined service `$class`."),
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
        return $this->mapperProvider->getMapper(
            $this->resolveRole($entity),
        );
    }

    public function getRepository(string|object $entity): RepositoryInterface
    {
        return $this->repositoryProvider->getRepository(
            $this->resolveRole($entity),
        );
    }

    public function getSource(string $entity): SourceInterface
    {
        return $this->sourceProvider->getSource(
            $this->resolveRole($entity),
        );
    }

    public function getIndexes(string $entity): array
    {
        return $this->indexProvider->getIndexes(
            $this->resolveRole($entity),
        );
    }

    /**
     * Get relation map associated with the given role or class.
     */
    public function getRelationMap(string $entity): RelationMap
    {
        return $this->relationProvider->getRelationMap(
            $this->resolveRole($entity),
        );
    }

    public function with(
        ?SchemaInterface $schema = null,
        ?FactoryInterface $factory = null,
        ?HeapInterface $heap = null,
    ): ORMInterface {
        $heap ??= clone $this->heap;
        $heap->clean();

        return new self(
            factory: $factory ?? $this->factory,
            schema: $schema ?? $this->schema,
            commandGenerator: $this->commandGenerator,
            heap: $heap,
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
     * Warmup ORM and preload all internal services.
     */
    public function prepareServices(): void
    {
        // Preload providers with back to ORM reference
        $this->relationProvider->prepareRelationMaps();
        $this->repositoryProvider->prepareRepositories();
        $this->mapperProvider->prepareMappers();
    }

    /**
     * @deprecated since Cycle ORM v1.8, this method will be removed in future releases.
     * Use method {@see with} instead.
     */
    public function withHeap(HeapInterface $heap): ORMInterface
    {
        return $this->with(heap: $heap);
    }

    /**
     * Reset related objects cache.
     */
    public function __clone()
    {
        $this->heap = clone $this->heap;
        $this->heap->clean();

        $this->resetRegistry();
    }

    public function __debugInfo(): array
    {
        return [
            'schema' => $this->schema,
        ];
    }

    private function resetRegistry(): void
    {
        $this->indexProvider = new IndexProvider($this->schema);
        $this->sourceProvider = new SourceProvider($this->factory, $this->schema);
        $this->typecastProvider = new TypecastProvider($this->factory, $this->schema, $this->sourceProvider);

        // With back to ORM reference
        $this->relationProvider = new RelationProvider($this);
        $this->mapperProvider = new MapperProvider($this, $this->factory);
        $this->repositoryProvider = new RepositoryProvider(
            $this,
            $this->sourceProvider,
            $this->schema,
            $this->factory,
        );
        $this->entityProvider = new EntityProvider($this->heap, $this->repositoryProvider);
        $this->roleResolver = new RoleResolver($this->schema, $this->heap);

        $this->entityFactory = new EntityFactory(
            $this->heap,
            $this->schema,
            $this->mapperProvider,
            $this->relationProvider,
            $this->indexProvider,
            $this->roleResolver,
        );
    }
}
