<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Mapper;

use Spiral\Cycle\Command\CommandInterface;
use Spiral\Cycle\Command\ContextCarrierInterface;
use Spiral\Cycle\Exception\MapperException;
use Spiral\Cycle\Heap\Node;

/**
 * Provides basic capabilities for CRUD operations with given entity class (role).
 */
interface MapperInterface
{
    /**
     * Get role name mapper is responsible for.
     *
     * @return string
     */
    public function getRole(): string;

    /**
     * Get repository associated with a given mapper.
     *
     * @return RepositoryInterface
     */
    public function getRepository(): RepositoryInterface;

    /**
     * Init empty entity object an return pre-filtered data (hydration will happen on a later stage). Must
     * return tuple [entity, entityData].
     *
     * @param array $data
     * @return array
     */
    public function init(array $data): array;

    /**
     * Hydrate entity with dataset.
     *
     * @param object $entity
     * @param array  $data
     * @return object
     *
     * @throws MapperException
     */
    public function hydrate($entity, array $data);

    /**
     * Extract all values from the entity.
     *
     * @param object $entity
     * @return array
     */
    public function extract($entity): array;

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object $entity
     * @param Node   $node
     * @return ContextCarrierInterface
     *
     * @throws MapperException
     */
    public function queueStore($entity, Node $node): ContextCarrierInterface;

    /**
     * Initiate sequence of of commands required to delete object from the persistent storage.
     *
     * @param object $entity
     * @param Node   $node
     * @return CommandInterface
     *
     * @throws MapperException
     */
    public function queueDelete($entity, Node $node): CommandInterface;
}