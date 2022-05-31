<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Exception\CollectionFactoryException;
use loophp\collection\Collection;
use loophp\collection\Contract\Collection as CollectionInterface;

/**
 * @template TCollection of Collection
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class LoophpCollectionFactory implements CollectionFactoryInterface
{
    /** @var class-string<TCollection> */
    private string $class = Collection::class;

    public function __construct()
    {
        if (!class_exists(Collection::class, true)) {
            throw new CollectionFactoryException(
                sprintf(
                    'There is no %s class. To resolve this issue you can install `loophp/collection` package.',
                    Collection::class
                )
            );
        }
    }

    public function getInterface(): ?string
    {
        return CollectionInterface::class;
    }

    public function withCollectionClass(string $class): static
    {
        $clone = clone $this;
        $clone->class = $class === Collection::class ? Collection::class : $class;
        return $clone;
    }

    public function collect(iterable $data): iterable
    {
        return ($this->class === Collection::class)
            ? Collection::fromIterable($data)
            : $this->class::fromIterable($data);
    }
}
