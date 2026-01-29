<?php

declare(strict_types=1);

namespace Cycle\ORM\Heap;

/**
 * NullHeap is a heap implementation that does nothing.
 * It is useful when you are using ORM only for reading and you don't need to cache the loaded data.
 *
 * Effects of using NullHeap:
 * - Less memory consumption
 * - No need to clean the heap after each request in long-living applications
 * - You won't be able to update entities in the database. ORM will consider each loaded entity as newly created.
 */
final class NullHeap implements HeapInterface
{
    public function has(object $entity): bool
    {
        return false;
    }

    public function get(object $entity): ?Node
    {
        return null;
    }

    public function find(string $role, array $scope): ?object
    {
        return null;
    }

    public function attach(object $entity, Node $node, array $index = []): void {}

    public function detach(object $entity): void {}

    public function clean(): void {}
}
