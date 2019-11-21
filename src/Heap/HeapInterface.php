<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM\Heap;

/**
 * Manages set of entities, their states and quick access through indexes.
 */
interface HeapInterface
{
    /**
     * Check if entity known to the heap.
     *
     * @param object $entity
     * @return bool
     */
    public function has($entity): bool;

    /**
     * Get Node associated with given entity.
     *
     * @param object $entity
     * @return Node|null
     */
    public function get($entity): ?Node;

    /**
     * Find object by key=>value scope. Attention, since all the keys are expected to be unique and indexed
     * the search will be completed on first value match.
     *
     * @param string $role
     * @param array  $scope
     * @return null|object
     */
    public function find(string $role, array $scope);

    /**
     * Attach entity to the heap and create index path.
     *
     * @param object $entity
     * @param Node   $node
     * @param array  $index
     */
    public function attach($entity, Node $node, array $index = []);

    /**
     * Detach entity from the Heap.
     *
     * @param object $entity
     */
    public function detach($entity);

    /**
     * Detach all objects from the heap.
     */
    public function clean();
}
