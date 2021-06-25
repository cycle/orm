<?php

declare(strict_types=1);

namespace Cycle\ORM\Relation;

use Cycle\ORM\Relation\Pivoted\PivotedCollectionInterface;

interface CollectionFactoryInterface
{
    public function collect(iterable $data): iterable;

    public function collectPivoted(iterable $data): PivotedCollectionInterface;
}
