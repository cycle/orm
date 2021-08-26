<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Illuminate\Support\Collection;
use RuntimeException;

/**
 * @template TCollection of Collection
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class IlluminateCollectionFactory implements CollectionFactoryInterface
{
    /** @var class-string<TCollection> */
    private string $class = Collection::class;

    public function __construct()
    {
        if (!class_exists(Collection::class, true)) {
            // todo: more friendly exception
            throw new RuntimeException(sprintf('There is no %s class.', Collection::class));
        }
    }

    public function withCollectionClass(string $class): static
    {
        $clone = clone $this;
        $clone->class = $class;
        return $clone;
    }

    public function collect(iterable $data): Collection
    {
        return new $this->class($data);
    }
}
