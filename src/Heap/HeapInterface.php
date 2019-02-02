<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Heap;

use Spiral\Cycle\Exception\HeapException;

/**
 * Manages set of entities, their states and quick access though indexes.
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
     * @param string $key
     * @param mixed  $value
     * @return null|object
     *
     * @throws HeapException
     */
    public function find(string $role, string $key, $value);

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