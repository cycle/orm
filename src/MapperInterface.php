<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Command\CommandInterface;
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
     */
    public function getRole(): string;

    /**
     * Init empty entity object. Returns empty entity.
     *
     * @param array $data Raw data. You shouldn't apply typecasting to it.
     */
    public function init(array $data, string $role = null): object;

    /**
     * Cast raw data to configured types.
     */
    public function cast(array $data): array;

    /**
     * Hydrate entity with dataset.
     *
     * @template T
     *
     * @param object<T> $entity
     * @param array $data Prepared (typecasted) data
     *
     * @throws MapperException
     *
     * @return T
     */
    public function hydrate(object $entity, array $data): object;

    /**
     * Extract all values from the entity.
     */
    public function extract(object $entity): array;

    /**
     * Get entity columns.
     */
    public function fetchFields(object $entity): array;

    /**
     * Get entity relation values.
     */
    public function fetchRelations(object $entity): array;

    /**
     * Map entity key->value to database specific column->value.
     * Original array also will be filtered: unused fields will be removed
     */
    public function mapColumns(array &$values): array;

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @throws MapperException
     */
    public function queueCreate(object $entity, Node $node, State $state): CommandInterface;

    /**
     * Initiate chain of commands required to update object in the persistent storage.
     *
     * @throws MapperException
     */
    public function queueUpdate(object $entity, Node $node, State $state): CommandInterface;

    /**
     * Initiate sequence of of commands required to delete object from the persistent storage.
     *
     * @throws MapperException
     */
    public function queueDelete(object $entity, Node $node, State $state): CommandInterface;
}
