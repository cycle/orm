<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\CommandPromiseInterface;
use Spiral\ORM\Exception\MapperException;

interface MapperInterface
{
    public function init();

    /**
     * Construct entity.
     *
     * @param array $data
     * @return object
     *
     * @throws MapperException
     */
    public function make(array $data);

    public function hydrate($entity, array $data);

    // todo: from the heap?
    public function getField($entity, $field);

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object $entity
     * @return CommandPromiseInterface
     *
     * @throws MapperException
     */
    public function queueStore($entity): CommandPromiseInterface;

    /**
     * Initiate sequence of of commands required to delete object from the persistent storage.
     *
     * @param object $entity
     * @return CommandInterface
     *
     * @throws MapperException
     */
    public function queueDelete($entity): CommandInterface;
}