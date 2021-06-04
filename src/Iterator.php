<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Heap\Node;

/**
 * Iterates over given data-set and instantiates objects.
 */
final class Iterator implements \IteratorAggregate
{
    /**
     * @var ORMInterface
     */
    private $orm;

    /**
     * @var string
     */
    private $role;

    /**
     * @var iterable
     */
    private $source;

    /**
     * @var bool
     */
    private $tryToFindInHeap;

    /**
     * @param ORMInterface $orm
     * @param string $class
     * @param iterable $source
     * @param bool $tryToFindInHeap
     */
    public function __construct(ORMInterface $orm, string $class, iterable $source, bool $tryToFindInHeap = false)
    {
        $this->orm = $orm;
        $this->role = $this->orm->resolveRole($class);
        $this->source = $source;
        $this->tryToFindInHeap = $tryToFindInHeap;
    }

    /**
     * Generate entities using incoming data stream. Pivoted data would be
     * returned as key value if set.
     *
     * @return \Generator
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

    private function getEntity(array $data)
    {
        if ($this->tryToFindInHeap) {
            $pk = $this->orm->getSchema()->define($this->role, SchemaInterface::PRIMARY_KEY);
            $id = $data[$pk] ?? null;

            if (null !== $id) {
                $e = $this->orm->getHeap()->find($this->role, [
                    $pk => $id,
                ]);
            }
        }

        return $e ?? $this->orm->make($this->role, $data, Node::MANAGED);
    }
}
