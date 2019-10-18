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
    /** @var ORMInterface */
    private $orm;

    /** @var string */
    private $class;

    /** @var iterable */
    private $source;

    /**
     * @param ORMInterface $orm
     * @param string       $class
     * @param iterable     $source
     */
    public function __construct(ORMInterface $orm, string $class, iterable $source)
    {
        $this->orm = $orm;
        $this->class = $class;
        $this->source = $source;
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

            yield $index => $this->orm->make($this->class, $data, Node::MANAGED);
        }
    }
}
