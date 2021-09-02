<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

/**
 * @template TCollection of array
 * @template-implements CollectionFactoryInterface<TCollection>
 */
final class ArrayCollectionFactory implements CollectionFactoryInterface
{
    /**
     * @psalm-param string $class
     */
    public function withCollectionClass(string $class): static
    {
        return $this;
    }

    public function collect(iterable $data): array
    {
        return \is_array($data) ? $data : \iterator_to_array($data);
    }
}
