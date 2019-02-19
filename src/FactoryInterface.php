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

interface FactoryInterface
{
    /**
     * Associate factory with ORM schema.
     *
     * @param ORMInterface    $orm
     * @param SchemaInterface $schema
     * @return FactoryInterface
     */
    public function withSchema(ORMInterface $orm, SchemaInterface $schema): FactoryInterface;

    /**
     * Create mapper associated with given role.
     *
     * @param string $role
     * @return MapperInterface
     */
    public function mapper(string $role): MapperInterface;

    /**
     * Create loader associated with specific entity and relation.
     *
     * @param string $role
     * @param string $relation
     * @return LoaderInterface
     */
    public function loader(string $role, string $relation): LoaderInterface;

    /**
     * Create relation associated with specific entity and relation.
     *
     * @param string $role
     * @param string $relation
     * @return RelationInterface
     */
    public function relation(string $role, string $relation): RelationInterface;
}