<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @template TCollection of Collection
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class DoctrineCollectionFactory implements CollectionFactoryInterface
{
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
        return new $this->class(is_array($data) ? $data : [...$data]);
    }
}
