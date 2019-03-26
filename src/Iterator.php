<?php declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
            // though-like relations
            if (isset($data['@'])) {
                $index = $data;
                unset($index['@']);
                $data = $data['@'];
            }

            yield $index => $this->orm->make($this->class, $data, Node::MANAGED);
        }
    }
}