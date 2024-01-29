<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Exception\CollectionFactoryException;

/**
 * Stores related items to array.
 * In the case of a relation loaded lazily in a proxy mapper,
 * you should note that writing to such array collection should take place after an overloading:
 *
 * <code>
 *
 *  // {$user->posts} is not loaded
 *
 *   // Bad code:
 *   $user->posts[] = new Post; // Exception or notice will be thrown
 *                              // because the {$user->posts} value comes from a __get method.
 *
 *   // Bad code:
 *   $posts = &$user->posts; // Exception or notice will be thrown
 *                           // because the {$user->posts} value comes from a __get method.
 *
 *   // Correct example:
 *   $user->post; // Resolve relation. It can be any `reading` code
 *   $user->post[] = new Post; //
 *
 * </code>
 *
 * @template TCollection of array
 *
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class ArrayCollectionFactory implements CollectionFactoryInterface
{
    public function getInterface(): ?string
    {
        return null;
    }

    /**
     * @psalm-param string $class
     */
    public function withCollectionClass(string $class): static
    {
        return $this;
    }

    public function collect(iterable $data): array
    {
        return match (true) {
            \is_array($data) => $data,
            $data instanceof \Traversable => \iterator_to_array($data),
            default => throw new CollectionFactoryException('Unsupported iterable type.'),
        };
    }
}
