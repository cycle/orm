<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection;

final class ArrayCollectionFactory implements CollectionFactoryInterface
{
    public function withCollectionClass(string $class): self
    {
        return $this;
    }

    public function collect(iterable $data): iterable
    {
        return is_array($data) ? $data : iterator_to_array($data);
    }
}
