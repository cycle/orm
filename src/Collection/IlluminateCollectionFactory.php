<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Exception\CollectionFactoryException;
use Illuminate\Support\Collection;

/**
 * @template TCollection of Collection
 *
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class IlluminateCollectionFactory implements CollectionFactoryInterface
{
    /** @var class-string<TCollection> */
    private string $class = Collection::class;

    public function __construct()
    {
        if (!\class_exists(Collection::class, true)) {
            throw new CollectionFactoryException(
                \sprintf(
                    'There is no %s class. To resolve this issue you can install `illuminate/collections` package.',
                    Collection::class,
                ),
            );
        }
    }

    public function getInterface(): ?string
    {
        return Collection::class;
    }

    public function withCollectionClass(string $class): static
    {
        $clone = clone $this;
        $clone->class = $class;
        return $clone;
    }

    public function collect(iterable $data): Collection
    {
        return new $this->class($data);
    }
}
