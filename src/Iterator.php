<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Node;

/**
 * Iterates over given data-set and instantiates objects.
 */
final class Iterator implements \IteratorAggregate
{
    private \Cycle\ORM\ORMInterface $orm;

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

            // add pipeline filter support?

            yield $index => $this->getEntity($data);
        }
    }

    private function getEntity(array $data): object
    {
        if ($this->findInHeap) {
            $pk = $this->orm->getSchema()->define($this->role, SchemaInterface::PRIMARY_KEY);
            if (is_array($pk)) {
                $e = $this->orm->getHeap()->find($this->role, $data);
            } else {
                $id = $data[$pk] ?? null;

                if ($id !== null) {
                    $e = $this->orm->getHeap()->find($this->role, [$pk => $id]);
                }
            }
        }

        return $e ?? $this->orm->make($this->role, $data, Node::MANAGED);
    }
}
