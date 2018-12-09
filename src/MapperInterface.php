<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Command\CommandInterface;
use Spiral\ORM\Command\ContextCarrierInterface;
use Spiral\ORM\Exception\MapperException;

interface MapperInterface
{
    public function getRepository(string $class = null): RepositoryInterface;

    // todo: need better stuff

    // todo: i don't like this
    public function prepare(array $data): array;

    public function hydrate($entity, array $data);

    public function extract($entity): array;

    /**
     * Initiate chain of commands require to store object and it's data into persistent storage.
     *
     * @param object $entity
     * @return ContextCarrierInterface
     *
     * @throws MapperException
     */
    public function queueStore($entity): ContextCarrierInterface;

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