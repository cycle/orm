<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface;

interface CollectionFactoryInterface
{
    public function withCollectionClass(string $class): self;

    /**
     * @return iterable|PivotedCollectionInterface
     */
    public function collect(iterable $data): iterable;
}
