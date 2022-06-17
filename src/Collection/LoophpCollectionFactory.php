<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Exception\CollectionFactoryException;
use loophp\collection\Collection;

/**
 * @template TCollection of Collection
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class LoophpCollectionFactory implements CollectionFactoryInterface
{
    /** @var class-string<TCollection> */
    private string $class = Collection::class;

    public function __construct()
    {
        if (!\class_exists(Collection::class, true)) {
            throw new CollectionFactoryException(
                \sprintf(
                    'There is no %s class. To resolve this issue you can install `loophp/collection` package.',
                    Collection::class
                )
            );
        }
    }

    public function getInterface(): string
    {
        return Collection::class;
    }

    public function withCollectionClass(string $class): static
    {
        if ($class !== Collection::class) {
            throw new CollectionFactoryException(\sprintf(
                'Unsupported collection class `%s`.',
                $class
            ));
        }
        return clone $this;
    }

    public function collect(iterable $data): Collection
    {
        return $this->class::fromIterable($data);
    }
}
