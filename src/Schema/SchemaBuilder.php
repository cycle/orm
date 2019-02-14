<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema;

class SchemaBuilder
{
    /** @var EntityInterface */
    private $entities = [];

    /**
     * Register new entity in schema.
     *
     * @param EntityInterface $entity
     */
    public function register(EntityInterface $entity)
    {
        $this->entities[] = $entity;
    }
}