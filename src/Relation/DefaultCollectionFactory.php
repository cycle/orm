<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Relation\Pivoted\PivotedCollection;
use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;
use Doctrine\Common\Collections\ArrayCollection;

final class DefaultCollectionFactory implements CollectionFactoryInterface
{
    public function collect(iterable $data): iterable
    {
        return new ArrayCollection(is_array($data) ? $data : [...$data]);
    }

    public function collectPivoted(iterable $data): PivotedCollectionInterface
    {
        return new PivotedCollection(is_array($data) ? $data : [...$data]);
    }
}
