<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\MapperException;

interface MapperInterface2
{


    /**
     * Construct data node. Given data must be treated as initial object data
     * and should not be passed thought any validation or filtration protocols.
     *
     * @param array       $data
     * @param RelationMap $relations
     * @return object
     *
     * @throws MapperException
     */
    public function make(array $data, RelationMap $relations);

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object $object
     * @param bool   $queueRelations
     * @return ContextualCommandInterface
     *
     * @throws MapperException
     */
    public function queueStore($object, bool $queueRelations = true): ContextualCommandInterface;

    /**
     * Initiate sequence of of commands required to delete object from the persistent storage.
     *
     * @param object $object
     * @return CommandInterface
     *
     * @throws MapperException
     */
    public function queueDelete($object): CommandInterface;
}