<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Select\LoaderInterface;
use Generator;
use IteratorAggregate;

/**
 * Iterates over given data-set and instantiates objects.
 *
 * @psalm-template TEntity of object
 *
 * @template-implements IteratorAggregate<array-key|array, TEntity>
 */
final class Iterator implements IteratorAggregate
{
    private string $role;

    /**
     * @param class-string<TEntity> $class
     * @param iterable<array-key, array> $source
     */
    public function __construct(
        private ORMInterface $orm,
        string $class,
        private iterable $source,
        private bool $findInHeap = false
    ) {
        $this->role = $orm->resolveRole($class);
    }

    /**
     * Generate entities using incoming data stream. Pivoted data would be
     * returned as key value if set.
     *
     * @return Generator<array-key|array, TEntity, mixed, void>
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
     * @param string|class-string<TEntity> $role
     *
     * @return TEntity
     */
    private function getEntity(array $data, string $role): object
    {
        if ($this->findInHeap) {
            $pk = $this->orm->getSchema()->define($role, SchemaInterface::PRIMARY_KEY);
            if (is_array($pk)) {
                $e = $this->orm->getHeap()->find($role, $data);
            } else {
                $id = $data[$pk] ?? null;

                if ($id !== null) {
                    $e = $this->orm->getHeap()->find($role, [$pk => $id]);
                }
            }
        }

        return $e ?? $this->orm->make($role, $data, Node::MANAGED);
    }
}
