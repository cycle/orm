<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Promise\ReferenceInterface;
use Cycle\ORM\Select\SourceProviderInterface;

/**
 * Provide the access to all ORM services.
 */
interface ORMInterface extends SourceProviderInterface
{
    /**
     * Automatically resolve role based on object name.
     *
     * @param string|object $entity
     * @return string
     */
    public function resolveRole($entity): string;

    /**
     * Get/load entity by unique key/value pair.
     *
     * @param string $role
     * @param array  $scope KV pair to locate the model, currently only support one pair.
     * @param bool   $load
     * @return object|null
     */
    public function get(string $role, array $scope, bool $load = true);

    /**
     * Create new entity based on given role and input data. Method will attempt to re-use
     * already loaded entity.
     *
     * @param string $role
     * @param array  $data
     * @param int    $node
     * @return object|null
     */
    public function make(string $role, array $data = [], int $node = Node::NEW);

    /**
     * Promise object reference, proxy or object from memory heap.
     *
     * @param string $role
     * @param array  $scope
     * @return ReferenceInterface|mixed|null
     */
    public function promise(string $role, array $scope);

    /**
     * Get factory for relations, mappers and etc.
     *
     * @return FactoryInterface
     */
    public function getFactory(): FactoryInterface;

    /**
     * Get ORM relation and entity schema provider.
     *
     * @return SchemaInterface
     */
    public function getSchema(): SchemaInterface;

    /**
     * Get current Heap (entity map).
     *
     * @return HeapInterface
     */
    public function getHeap(): HeapInterface;

    /**
     * Get mapper associated with given entity class, role or instance.
     *
     * @param string|object $entity
     * @return MapperInterface
     */
    public function getMapper($entity): MapperInterface;

    /**
     * Get repository associated with given entity.
     *
     * @param string|object $entity
     * @return RepositoryInterface
     */
    public function getRepository($entity): RepositoryInterface;

    /**
     * Generate chain of commands required to store given entity and it's relations.
     *
     * @param object $entity
     * @param int    $mode
     * @return ContextCarrierInterface
     */
    public function queueStore($entity, int $mode = TransactionInterface::MODE_CASCADE): ContextCarrierInterface;

    /**
     * Generate commands required to delete the entity.
     *
     * @param object $entity
     * @param int    $mode
     * @return CommandInterface
     */
    public function queueDelete($entity, int $mode = TransactionInterface::MODE_CASCADE): CommandInterface;
}
