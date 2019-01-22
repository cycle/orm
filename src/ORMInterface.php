<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Heap\HeapInterface;
use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Mapper\MapperInterface;

/**
 * Provide the access to all ORM services.
 */
interface ORMInterface
{
    /**
     * Automatically resolve role based on object name.
     *
     * @param string|object $entity
     * @return string
     */
    public function resolveRole($entity): string;

    /**
     * Get entity by it's primary key from the heap of automatically load it.
     *
     * @param string $role
     * @param mixed  $id
     * @param bool   $load
     * @return object|null
     */
    public function get(string $role, $id, bool $load = true);

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