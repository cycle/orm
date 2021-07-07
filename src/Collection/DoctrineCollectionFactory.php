<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;
use Cycle\ORM\Relation\Pivoted\PivotedStorage;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

final class DoctrineCollectionFactory implements CollectionFactoryInterface
{
    private string $class = ArrayCollection::class;

    public function withCollectionClass(string $class): self
    {
        $clone = clone $this;
        $clone->class = $class === Collection::class ? ArrayCollection::class : $class;
        return $clone;
    }

    public function collect(iterable $data): iterable
    {
        return new $this->class(is_array($data) ? $data : [...$data]);
    }

    public function collectPivoted(iterable $data): PivotedCollectionInterface
    {
        if ($data instanceof PivotedStorage) {
            return new PivotedCollection($data->getElements(), $data->getContext());
        }
        return new PivotedCollection(is_array($data) ? $data : [...$data]);
    }
}
