<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

use Cycle\ORM\Collection\Pivoted\LoophpPivotedCollection;
use Cycle\ORM\Collection\Pivoted\PivotedStorage;
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

    private bool $decoratorExists = false;

    public function __construct()
    {
        if (!\class_exists(Collection::class, true)) {
            throw new CollectionFactoryException(
                \sprintf(
                    'There is no %s class. To resolve this issue you can install `loophp/collection` package.',
                    Collection::class,
                ),
            );
        }

        if (\class_exists(CollectionDecorator::class, true)) {
            $this->decoratorExists = true;
        }
    }

    public function getInterface(): string
    {
        return CollectionInterface::class;
    }

    public function withCollectionClass(string $class): static
    {
        if (
            $this->decoratorExists &&
            (\is_a($class, CollectionDecorator::class, true) || $class === CollectionInterface::class)
        ) {
            $clone = clone $this;
            $clone->class = $class === CollectionInterface::class ? Collection::class : $class;

            return $clone;
        }

        if ($class !== Collection::class) {
            throw new CollectionFactoryException(\sprintf(
                'Unsupported collection class `%s`.',
                $class,
            ));
        }

        return clone $this;
    }

    public function collect(iterable $data): CollectionInterface
    {
        if ($data instanceof PivotedStorage && $this->decoratorExists) {
            if ($this->class === Collection::class || $this->class === CollectionInterface::class) {
                return new LoophpPivotedCollection($data->getElements(), $data->getContext());
            }

            if (\is_a($this->class, LoophpPivotedCollection::class)) {
                return new $this->class($data->getElements(), $data->getContext());
            }
        }

        return $this->class::fromIterable($data);
    }
}
