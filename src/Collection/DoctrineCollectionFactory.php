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
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class DoctrineCollectionFactory implements CollectionFactoryInterface
{
    public function __construct()
    {
        if (!class_exists(ArrayCollection::class, true)) {
            throw new CollectionFactoryException(
                sprintf(
                    'There is no %s class. To resolve this issue you can install `doctrine/collections` package.',
                    ArrayCollection::class
                )
            );
        }
    }

    /** @var class-string<TCollection> */
    private string $class = ArrayCollection::class;

    public function withCollectionClass(string $class): static
    {
        $clone = clone $this;
        $clone->class = $class === Collection::class ? ArrayCollection::class : $class;
        return $clone;
    }

    public function collect(iterable $data): Collection
    {
        if ($data instanceof PivotedStorage && $this->class === ArrayCollection::class) {
            return new PivotedCollection($data->getElements(), $data->getContext());
        }
        return new $this->class(\is_array($data) ? $data : [...$data]);
    }
}
