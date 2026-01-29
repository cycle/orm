<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Cycle\ORM\Exception\CollectionFactoryException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Stores related items doctrine collection.
 * Items and Pivots for `Many to Many` relation stores in {@see PivotedCollection}.
 *
 * @template TCollection of Collection
 *
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class DoctrineCollectionFactory implements CollectionFactoryInterface
{
    /** @var class-string<TCollection> */
    private string $class = ArrayCollection::class;

    public function __construct()
    {
        if (!\class_exists(ArrayCollection::class, true)) {
            throw new CollectionFactoryException(
                \sprintf(
                    'There is no %s class. To resolve this issue you can install `doctrine/collections` package.',
                    ArrayCollection::class,
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
        $clone->class = $class === Collection::class ? ArrayCollection::class : $class;
        return $clone;
    }

    public function collect(iterable $data): Collection
    {
        if ($data instanceof PivotedStorage) {
            if ($this->class === ArrayCollection::class) {
                return new PivotedCollection($data->getElements(), $data->getContext());
            }

            if (\is_a($this->class, PivotedCollection::class)) {
                return new $this->class($data->getElements(), $data->getContext());
            }
        }

        $data = match (true) {
            \is_array($data) => $data,
            $data instanceof \Traversable => \iterator_to_array($data),
            default => throw new CollectionFactoryException('Unsupported iterable type.'),
        };

        return new $this->class($data);
    }
}
