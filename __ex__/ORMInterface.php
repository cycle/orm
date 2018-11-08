<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\MapperException;
use Spiral\Treap\Exception\ORMException;

interface ORMInterface
{
    /**
     * Return schema declaration associated with given class.
     *
     * @param string $class
     * @return Schema
     *
     * @throws ORMException
     */
    public function getSchema(string $class): Schema;

    //    /**
    //     * Return mapper associated with given class.
    //     *
    //     * @param string $class
    //     * @return MapperInterface
    //     *
    //     * @throws ORMException
    //     */
    //    public function getMapper(string $class): MapperInterface;

    /**
     * Instantiate and hydrate entity based on given class and input data-set. Method will return cached object
     * if any found.
     *
     * @param string $class
     * @param array  $data
     * @param int    $state
     * @param bool   $cache Add entity into Heap.
     * @return object
     *
     * @throws MapperException
     * @throws ORMException
     */
    public function make(
        string $class,
        array $data = [],
        int $state = MapperInterface::STATE_NEW,
        bool $cache = false
    );
}