<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

/**
 * Manages set of entities, their states and quick access through indexes.
 */
interface HeapInterface
{
    /**
     * Check if entity known to the heap.
     */
    public function has(object $entity): bool;

    /**
     * Get Node associated with given entity.
     */
    public function get(object $entity): ?Node;

    /**
     * Find object by key=>value scope. Attention, since all the keys are expected to be unique and indexed
     * the search will be completed on first value match.
     */
    public function find(string $role, array $scope): ?object;

    /**
     * Attach entity to the heap and create index path.
     */
    public function attach(object $entity, Node $node, array $index = []): void;

    /**
     * Detach entity from the Heap.
     */
    public function detach(object $entity): void;

    /**
     * Detach all objects from the heap.
     */
    public function clean(): void;
}
