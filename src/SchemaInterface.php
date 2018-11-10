<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM;

use Spiral\ORM\Exception\SchemaException;

interface SchemaInterface
{
    public const ALIAS       = 0;
    public const MAPPER      = 1;
    public const SOURCE      = 2;
    public const DATABASE    = 3;
    public const TABLE       = 4;
    public const PRIMARY_KEY = 5;
    public const COLUMNS     = 6;
    public const RELATIONS   = 8;

    /**
     * Define schema value.
     *
     * Example: $schema->define(User::class, SchemaInterface::DATABASE);
     *
     * @param string $class
     * @param int    $property See ORM constants.
     * @return mixed
     *
     * @throws SchemaException
     */
    public function define(string $class, int $property);

    /**
     * Define options associated with specific entity relation.
     *
     * @param string $class
     * @param string $relation
     * @return array
     *
     * @throws SchemaException
     */
    public function defineRelation(string $class, string $relation): array;
}