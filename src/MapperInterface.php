<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCommandInterface;
use Spiral\ORM\Exception\MapperException;

interface MapperInterface
{
    public function entityClass(array $data): string;

    public function init(string $class);

    public function hydrate($entity, array $data);

    public function extract($entity): array;

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object $entity
     * @return ContextCommandInterface
     *
     * @throws MapperException
     */
    public function queueStore($entity): ContextCommandInterface;

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