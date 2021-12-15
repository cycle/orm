<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

/**
 * @template TCollection
 */
interface CollectionFactoryInterface
{
    /**
     * @return class-string|null
     */
    public function getInterface(): ?string;

    /**
     * @param class-string<TCollection> $class
     */
    public function withCollectionClass(string $class): static;

    /**
     * @template TKey
     * @template TValue of array|object
     *
     * @param iterable<TKey, TValue> $data
     *
     * @return TCollection
     *
     * @psalm-return TCollection|iterable<TKey, TValue>
     */
    public function collect(iterable $data): iterable;
}
