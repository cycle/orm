<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\PivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedCollectionInterface;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
use Illuminate\Support\Collection;

final class IlluminateCollectionFactory implements CollectionFactoryInterface
{
    private string $class = Collection::class;

    public function __construct()
    {
        if (!class_exists(Collection::class, true)) {
            // todo: more friendly exception
            throw new \RuntimeException(sprintf('There is no %s class.', Collection::class));
        }
    }

    public function withCollectionClass(string $class): CollectionFactoryInterface
    {
        $clone = clone $this;
        $clone->class = $class;
        return $clone;
    }

    public function collect(iterable $data): iterable
    {
        return new $this->class($data);
    }
}
