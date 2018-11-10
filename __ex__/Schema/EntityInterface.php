<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Schema;

interface EntityInterface
{
    /**
     * Each entity must have alias name associated with it.
     * It's not required to keep aliases unique at this moment
     * as collision is only possible in case of polymorphic
     * relations.
     *
     * @return string
     */
    public function getAlias(): string;

    /**
     * Entity object class name.
     *
     * @return string
     */
    public function getClass(): string;

    /**
     * Entity object class reflection.
     *
     * @return \ReflectionClass
     */
    public function getReflection(): \ReflectionClass;

    /**
     * Get declaration of table object. Table declaration would
     * contain all needed columns, indexes and FK.
     *
     * @return TableInterface
     */
    public function getTable(): TableInterface;

    /**
     * Return list of all declared relations.
     *
     * @return RelationInterface[]
     */
    public function getRelations(): array;
}