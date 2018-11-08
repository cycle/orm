<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Database\DatabaseInterface;
use Spiral\Treap\Exception\SchemaException;

interface ORMInterface
{
    /**
     * Define schema value.
     *
     * Example: $schema->define(User::class, Schema::DATABASE);
     *
     * @param string $class
     * @param int    $property See ORM constants.
     * @return mixed
     *
     * @throws SchemaException
     */
    public function define(string $class, int $property);

    /**
     * @param string $class
     * @return DatabaseInterface
     */
    public function database(string $class): DatabaseInterface;

    /**
     * @param string $class
     * @return Selector
     */
    public function selector(string $class): Selector;








    //    /**
    //     * Instantiate and hydrate entity based on given class and input data-set. Method will return cached object
    //     * if any found.
    //     *
    //     * @param string $class
    //     * @param array  $data
    //     * @param int    $state
    //     * @param bool   $cache Add entity into Heap.
    //     * @return object
    //     *
    //     * @throws MapperException
    //     * @throws ORMException
    //     */
    //    public function make(
    //        string $class,
    //        array $data = [],
    //        int $state = MapperInterface::STATE_NEW,
    //        bool $cache = false
    //    );
}