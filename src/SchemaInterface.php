<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\SchemaException;

interface SchemaInterface
{
    /*
     * Various segments of schema.
     */
    public const ROLE = 0;
    public const ENTITY = 1;
    public const MAPPER = 2;
    public const SOURCE = 3;
    public const REPOSITORY = 4;
    public const DATABASE = 5;
    public const TABLE = 6;
    public const PRIMARY_KEY = 7;
    public const FIND_BY_KEYS = 8;
    public const COLUMNS = 9;
    public const RELATIONS = 10;
    public const CHILDREN = 11;
    public const SCOPE = 12;
    public const TYPECAST = 13;
    public const SCHEMA = 14;

    /** @deprecated Use {@see SchemaInterface::SCOPE} instead. */
    public const CONSTRAIN = self::SCOPE;

    /**
     * Return all roles defined within the schema.
     *
     * @return array
     */
    public function getRoles(): array;

    /**
     * Get name of relations associated with given entity role.
     *
     * @param string $role
     *
     * @return array
     */
    public function getRelations(string $role): array;

    /**
     * Check if the given role has a definition within the schema.
     *
     * @param string $role
     *
     * @return bool
     */
    public function defines(string $role): bool;

    /**
     * Define schema value.
     *
     * Example: $schema->define(User::class, SchemaInterface::DATABASE);
     *
     * @param string $role
     * @param int    $property See ORM constants.
     *
     * @throws SchemaException
     *
     * @return mixed
     */
    public function define(string $role, int $property);

    /**
     * Define options associated with specific entity relation.
     *
     * @param string $role
     * @param string $relation
     *
     * @throws SchemaException
     *
     * @return array
     */
    public function defineRelation(string $role, string $relation): array;

    /**
     * Resolve the role name using entity class name.
     *
     * @param string $role
     *
     * @return string|null
     */
    public function resolveAlias(string $role): ?string;
}
