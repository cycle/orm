<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Heap\Node;
use Spiral\Cycle\Parser\PivotedNode;

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
            if (isset($data[PivotedNode::PIVOT_DATA])) {
                // when pivot data is provided we are going to use it as array key.
                $index = $data[PivotedNode::PIVOT_DATA];
                unset($data[PivotedNode::PIVOT_DATA]);
            }

            yield $index => $this->orm->make($this->class, $data, Node::MANAGED);
        }
    }
}