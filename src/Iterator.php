<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\HeapInterface;
use Cycle\ORM\Heap\Node;
use Cycle\ORM\Service\EntityFactoryInterface;
use Cycle\ORM\Select\LoaderInterface;
use Generator;
use IteratorAggregate;

/**
 * Iterates over given data-set and instantiates objects.
 *
 * @template TEntity
 *
 * @template-implements IteratorAggregate<array-key|array, TEntity>
 */
final class Iterator implements IteratorAggregate
{
    private function __construct(
        private string $role,
        private HeapInterface $heap,
        private SchemaInterface $schema,
        private EntityFactoryInterface $entityFactory,
        private iterable $source,
        private bool $findInHeap = false,
        private bool $typecast = false
    ) {
    }

    /**
     * @param class-string<TEntity>|string $class
     * @param iterable<array-key, array> $source
     */
    public static function createWithOrm(
        ORMInterface $orm,
        string $class,
        iterable $source,
        bool $findInHeap = false,
        bool $typecast = false
    ): self {
        return new self(
            $orm->resolveRole($class),
            $orm->getHeap(),
            $orm->getSchema(),
            $orm->getService(EntityFactoryInterface::class),
            $source,
            $findInHeap,
            $typecast
        );
    }

    /**
     * @param non-empty-string $role
     * @param iterable<array-key, array> $source
     */
    public static function createWithServices(
        HeapInterface $heap,
        SchemaInterface $schema,
        EntityFactoryInterface $entityProvider,
        string $role,
        iterable $source,
        bool $findInHeap = false,
        bool $typecast = false
    ): self {
        return new self(
            $role,
            $heap,
            $schema,
            $entityProvider,
            $source,
            $findInHeap,
            $typecast
        );
    }

    /**
     * Generate entities using incoming data stream. Pivoted data would be
     * returned as key value if set.
     *
     * @return Generator<array, array-key|TEntity, mixed, void>
     */
    public function getIterator(): Generator
    {
        foreach ($this->source as $index => $data) {
            // through-like relations
            if (isset($data['@'])) {
                $index = $data;
                unset($index['@']);
                $data = $data['@'];
            }

            // get role from joined table inheritance
            $role = $data[LoaderInterface::ROLE_KEY] ?? $this->role;

            // add pipeline filter support?

            yield $index => $this->getEntity($data, $role);
        }
    }

    /**
     * @param class-string<TEntity>|string $role
     *
     * @return TEntity
     */
    private function getEntity(array $data, string $role): object
    {
        if ($this->findInHeap) {
            $pk = $this->schema->define($role, SchemaInterface::PRIMARY_KEY);
            if (\is_array($pk)) {
                $e = $this->heap->find($role, $data);
            } else {
                $id = $data[$pk] ?? null;

                if ($id !== null) {
                    $e = $this->heap->find($role, [$pk => $id]);
                }
            }
        }

        return $e ?? $this->entityFactory->make($role, $data, Node::MANAGED, typecast: $this->typecast);
    }
}
