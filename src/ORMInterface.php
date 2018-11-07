<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\MapperException;

interface ORMInterface
{
    /**
     * Instantiate and hydrate entity based on given class and input data-set.
     *
     * @param string $class
     * @param array  $data
     * @param int    $state
     * @param bool   $cache Add entity into Heap.
     * @return object
     *
     * @throws MapperException
     */
    public function make(
        string $class,
        array $data = [],
        int $state = MapperInterface::STATE_NEW,
        bool $cache = false
    );
}