<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Reference\ReferenceInterface;
use Cycle\ORM\Select\SourceProviderInterface;

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
     * @param string|class-string<T> $role
     * @return T
     * @psalm-return ($role is class-string ? T : object)
     */
    public function make(string $role, array $data = [], int $status = Node::NEW): object;

    /**
     * Promise object reference, proxy or object from memory heap.
     *
     * @return ReferenceInterface|object
     */
    public function promise(string $role, array $scope): object;

    /**
     * Get factory for relations, mappers and etc.
     */
    public function getFactory(): FactoryInterface;

    /**
     * Get ORM relation and entity schema provider.
     */
    public function getSchema(): SchemaInterface;

    /**
     * Get current Heap (entity map).
     */
    public function getHeap(): HeapInterface;

    /**
     * Get mapper associated with given entity class, role or instance.
     */
    public function getMapper(string|object $entity): MapperInterface;

    /**
     * Get repository associated with given entity.
     */
    public function getRepository(string|object $entity): RepositoryInterface;

    public function withSchema(SchemaInterface $schema): ORMInterface;

    public function withHeap(HeapInterface $heap): ORMInterface;
}
