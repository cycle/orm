<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Node;
use Cycle\ORM\Select\LoaderInterface;

/**
 * Iterates over given data-set and instantiates objects.
 */
final class Iterator implements \IteratorAggregate
{
    private ORMInterface $orm;

    private string $role;

    private iterable $source;

    private bool $findInHeap;

    public function __construct(ORMInterface $orm, string $class, iterable $source, bool $findInHeap = false)
    {
        $this->orm = $orm;
        $this->role = $orm->resolveRole($class);
        $this->source = $source;
        $this->findInHeap = $findInHeap;
    }

    /**
     * Generate entities using incoming data stream. Pivoted data would be
     * returned as key value if set.
     */
    public function getIterator(): \Generator
    {
        foreach ($this->source as $index => $data) {
            // through-like relations
            if (isset($data['@'])) {
                $index = $data;
                unset($index['@']);
                $data = $data['@'];
            }

            // get role from joined table inheritance
            $role = $data[LoaderInterface::DISCRIMINATOR_KEY] ?? $this->role;

            // add pipeline filter support?

            yield $index => $this->getEntity($data, $role);
        }
    }

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
