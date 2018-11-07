<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\MapperException;

interface MapperInterface
{
    const STATE_NEW              = 0;
    const STATE_LOADED           = 1;
    const STATE_DELETED          = 2;
    const STATE_SCHEDULED        = 100;
    const STATE_SCHEDULED_INSERT = self::STATE_SCHEDULED | 4;
    const STATE_SCHEDULED_UPDATE = self::STATE_SCHEDULED | 5;
    const STATE_SCHEDULED_DELETE = self::STATE_SCHEDULED | 6;

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