<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM;

use Cycle\ORM\Relation\RelationInterface;
use Cycle\ORM\Select\LoaderInterface;
use Psr\Container\ContainerInterface;
use Spiral\Database\DatabaseInterface;

/**
 * Must provide access to generic DI.
 */
interface FactoryInterface extends ContainerInterface
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

    /**
     * Get database by it's name.
     *
     * @param string $database
     * @return DatabaseInterface
     */
    public function database(string $database): DatabaseInterface;
}