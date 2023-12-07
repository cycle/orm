<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Exception\CollectionFactoryException;
use loophp\collection\Collection;
use loophp\collection\CollectionDecorator;
use loophp\collection\Contract\Collection as CollectionInterface;

/**
 * @template TCollection of Collection
 *
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
        return CollectionInterface::class;
    }

    public function withCollectionClass(string $class): static
    {
        if (
            \class_exists(CollectionDecorator::class) &&
            (\is_a($class, CollectionDecorator::class, true) || $class === CollectionInterface::class)
        ) {
            $clone = clone $this;
            $clone->class = $class === CollectionInterface::class ? Collection::class : $class;

            return $clone;
        }

        if ($class !== Collection::class) {
            throw new CollectionFactoryException(\sprintf(
                'Unsupported collection class `%s`.',
                $class
            ));
        }

        return clone $this;
    }

    public function collect(iterable $data): CollectionInterface
    {
        return $this->class::fromIterable($data);
    }
}
