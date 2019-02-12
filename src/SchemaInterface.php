<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle;

use Spiral\Cycle\Exception\SchemaException;

interface SchemaInterface
{
    public const ENTITY       = -1;
    public const ROLE         = 0;
    public const MAPPER       = 1;
    public const SOURCE       = 2;
    public const REPOSITORY   = 99;
    public const DATABASE     = 3;
    public const TABLE        = 4;
    public const PRIMARY_KEY  = 5;
    public const FIND_BY_KEYS = 6;
    public const COLUMNS      = 7;
    public const COLUMN_TYPES = 8;
    public const SCHEMA       = 9;
    public const RELATIONS    = 10;
    public const CHILDREN     = 12;
    public const CONSTRAIN    = 13;
    public const TYPECAST     = 14;

    public function defines(string $role): bool;

    /**
     * Define schema value.
     *
     * Example: $schema->define(User::class, SchemaInterface::DATABASE);
     *
     * @param string $role
     * @param int    $property See ORM constants.
     * @return mixed
     *
     * @throws SchemaException
     */
    public function define(string $role, int $property);

    /**
     * @param string $alias
     * @return null|string
     */
    public function resolveRole(string $alias): ?string;

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