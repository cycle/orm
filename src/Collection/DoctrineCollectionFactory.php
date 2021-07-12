<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
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
        if ($data instanceof PivotedStorage) {
            return new PivotedCollection($data->getElements(), $data->getContext());
        }
        return new $this->class(is_array($data) ? $data : [...$data]);
    }
}
