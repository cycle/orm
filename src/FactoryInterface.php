<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Spiral\Core\FactoryInterface as CoreFactory;
use Spiral\Database\DatabaseProviderInterface;

/**
 * Must provide access to generic DI.
 */
interface FactoryInterface extends DatabaseProviderInterface, CoreFactory
{
    /**
     * Create mapper associated with given role.
     *
     * @param ORMInterface    $orm
     * @param SchemaInterface $schema
     * @param string          $role
     * @return MapperInterface
     */
    public function mapper(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role
    ): MapperInterface;

    /**
     * Create loader associated with specific entity and relation.
     *
     * @param ORMInterface    $orm
     * @param SchemaInterface $schema
     * @param string          $role
     * @param string          $relation
     * @return LoaderInterface
     */
    public function loader(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): LoaderInterface;

    /**
     * Create relation associated with specific entity and relation.
     *
     * @param ORMInterface    $orm
     * @param SchemaInterface $schema
     * @param string          $role
     * @param string          $relation
     * @return RelationInterface
     */
    public function relation(
        ORMInterface $orm,
        SchemaInterface $schema,
        string $role,
        string $relation
    ): RelationInterface;
}
