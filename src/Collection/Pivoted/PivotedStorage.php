<?php

declare(strict_types=1);

namespace Cycle\ORM\Collection\Pivoted;

/**
 * Carry information about ordered list of entities and associated pivot context.
 *
 * @template TEntity of object
 * @template TPivot of object|array
 */
class PivotedStorage implements \IteratorAggregate, \Countable
{
    /** @var TEntity[] */
    private array $elements;

    /** @var \SplObjectStorage<TEntity, TPivot> */
    private \SplObjectStorage $context;

    /**
     * @param TEntity[] $elements
     * @param \SplObjectStorage<TEntity, TPivot>|null $context
     */
    public function __construct(array $elements = [], ?\SplObjectStorage $context = null)
    {
        $this->elements = $elements;
        $this->context = $context ?? new \SplObjectStorage();
    }

    /**
     * @return TEntity[]
     */
    public function getElements(): array
    {
        return $this->elements;
    }

    public function getIterator(): \Generator
    {
        yield from $this->getElements();
    }

    public function getContext(): \SplObjectStorage
    {
        return $this->context;
    }

    /**
     * Check if entity belongs to the storage.
     */
    public function has(object $entity): bool
    {
        return \in_array($entity, $this->elements, true);
    }

    /**
     * @param TEntity $entity
     */
    public function hasContext(object $entity): bool
    {
        return $this->context->offsetExists($entity);
    }

    /**
     * Get entity context.
     *
     * @param TEntity $entity
     *
     * @return TPivot|null
     */
    public function get(object $entity): object|array|null
    {
        try {
            return $this->context->offsetGet($entity);
        } catch (\UnexpectedValueException) {
            return null;
        }
    }

    /**
     * Get entity context.
     *
     * @param TEntity $entity
     * @param TPivot $pivot
     */
    public function set(object $entity, object|array $pivot): void
    {
        $this->context->offsetSet($entity, $pivot);
    }

    public function count(): int
    {
        return \count($this->elements);
    }
}
