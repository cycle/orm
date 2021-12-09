<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Registry\SourceProviderInterface;
use Cycle\ORM\Transaction\CommandGeneratorInterface;

/**
 * Provide the access to all ORM services.
 */
interface ORMInterface extends SourceProviderInterface
{
    /**
     * Automatically resolve role based on object name or instance.
     */
    public function resolveRole(string|object $entity): string;

    /**
     * Get/load entity by unique key/value pair.
     *
     * @param array  $scope KV pair to locate the model, currently only support one pair.
     */
    public function get(string $role, array $scope, bool $load = true): ?object;

    /**
     * Create new entity based on given role and input data. Method will attempt to re-use
     * already loaded entity.
     *
     * @template T
     *
     * @param class-string<T>|string $role Entity role or class name.
     * @param bool $typecast Indicates that data is raw, and typecasting should be applied.
     *
     * @return T
     * @psalm-return ($role is class-string ? T : object)
     */
    public function make(string $role, array $data = [], int $status = Node::NEW, bool $typecast = false): object;

    /**
     * Promise object reference, proxy or object from memory heap.
     *
     * @return object|ReferenceInterface
     */
    public function promise(string $role, array $scope): object;

    /**
     * Get factory for relations, mappers and etc.
     */
    public function getFactory(): FactoryInterface;

    /**
     * Get configured Event Dispatcher.
     */
    public function getCommandGenerator(): CommandGeneratorInterface;

    /**
     * Get entity registry.
     */
    public function getEntityRegistry(): EntityRegistryInterface;

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
        ?HeapInterface $heap = null
    ): self;

    /**
     * Get mapper associated with given entity class, role or instance.
     */
    public function getMapper(string|object $entity): MapperInterface;

    /**
     * Get repository associated with given entity class, role or instance.
     */
    public function getRepository(string|object $entity): RepositoryInterface;
}
