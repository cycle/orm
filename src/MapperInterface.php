<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\CommandInterface;
use Cycle\ORM\Command\ContextCarrierInterface;
use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Heap\State;

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
    public function hydrate(object $entity, array $data): object;

    /**
     * Extract all values from the entity.
     *
     * @param object $entity
     * @return array
     */
    public function extract(object $entity): array;

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @throws MapperException
     */
    public function queueCreate(object $entity, Node $node, State $state): ContextCarrierInterface;

    /**
     * Initiate chain of commands required to update object in the persistent storage.
     *
     * @throws MapperException
     */
    public function queueUpdate(object $entity, Node $node, State $state): ContextCarrierInterface;

    /**
     * Initiate sequence of of commands required to delete object from the persistent storage.
     *
     * @throws MapperException
     */
    public function queueDelete(object $entity, Node $node, State $state): CommandInterface;
}
