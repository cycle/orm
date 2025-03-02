<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Service\EntityProviderInterface;
use Cycle\ORM\Service\IndexProviderInterface;
use Cycle\ORM\Service\MapperProviderInterface;
use Cycle\ORM\Service\RelationProviderInterface;
use Cycle\ORM\Service\RepositoryProviderInterface;
use Cycle\ORM\Service\RoleResolverInterface;
use Cycle\ORM\Service\SourceProviderInterface;
use Cycle\ORM\Transaction\CommandGeneratorInterface;

/**
 * Provide the access to all ORM services.
 */
interface ORMInterface extends
    EntityFactoryInterface,
    EntityProviderInterface,
    SourceProviderInterface,
    MapperProviderInterface,
    RepositoryProviderInterface,
    RelationProviderInterface,
    RoleResolverInterface,
    IndexProviderInterface
{
    /**
     * Create new entity based on given role and input data. Method will attempt to re-use
     * already loaded entity.
     *
     * @template TEntity
     *
     * @param class-string<TEntity>|string $role Entity role or class name.
     * @param array<string, mixed> $data Entity data.
     * @param bool $typecast Indicates that data is raw, and typecasting should be applied.
     *
     * @return TEntity
     *
     * @psalm-return ($role is class-string ? TEntity : object)
     */
    public function make(string $role, array $data = [], int $status = Node::NEW, bool $typecast = false): object;

    /**
     * Get factory for relations, mappers and etc.
     */
    public function getFactory(): FactoryInterface;

    /**
     * Get configured Event Dispatcher.
     */
    public function getCommandGenerator(): CommandGeneratorInterface;

    /**
     * @template Provider
     *
     * @param class-string<Provider> $class
     *
     * @return Provider
     */
    public function getService(string $class): object;

    /**
     * Get ORM relation and entity schema provider.
     */
    public function getSchema(): SchemaInterface;

    /**
     * Get current Heap (entity map).
     */
    public function getHeap(): HeapInterface;

    public function with(
        ?SchemaInterface $schema = null,
        ?FactoryInterface $factory = null,
        ?HeapInterface $heap = null,
    ): self;

    /**
     * Get mapper associated with given entity class, role or instance.
     *
     * @param non-empty-string|object $entity
     */
    public function getMapper(string|object $entity): MapperInterface;

    /**
     * Get repository associated with given entity class, role or instance.
     *
     * @template TEntity of object
     *
     * @param class-string<TEntity>|non-empty-string|TEntity $entity
     *
     * @return RepositoryInterface<TEntity>
     *
     * @psalm-return ($entity is class-string ? RepositoryInterface<TEntity> : RepositoryInterface)
     */
    public function getRepository(string|object $entity): RepositoryInterface;
}
