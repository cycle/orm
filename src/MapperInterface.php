<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Command\CommandInterface;
use Spiral\Treap\Command\CommandPromiseInterface;
use Spiral\Treap\Exception\MapperException;

interface MapperInterface
{
    /**
     * Construct entity.
     *
     * @param array       $data
     * @param RelationMap $relmap
     * @return object
     *
     * @throws MapperException
     */
    public function make(array $data, RelationMap $relmap = null);

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object      $object
     * @param RelationMap $relmap       @todo is it needed here?
     * @return CommandPromiseInterface
     *
     * @throws MapperException
     */
    public function queueStore($object, RelationMap $relmap = null): CommandPromiseInterface;

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