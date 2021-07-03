<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;
use Cycle\ORM\Relation\Pivoted\PivotedStorage;
use Doctrine\Common\Collections\ArrayCollection;

final class DoctrineCollectionFactory implements CollectionFactoryInterface
{
    public function collect(iterable $data): iterable
    {
        return new ArrayCollection(is_array($data) ? $data : [...$data]);
    }

    public function collectPivoted(iterable $data): PivotedCollectionInterface
    {
        if ($data instanceof PivotedStorage) {
            return new PivotedCollection($data->getElements(), $data->getContext());
        }
        return new PivotedCollection(is_array($data) ? $data : [...$data]);
    }
}
