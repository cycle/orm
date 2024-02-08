<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\SchemaException;

interface SchemaInterface
{
    /*
     * Various segments of schema.
     */
    public const ROLE = 0;
    public const ENTITY = 1; // Entity Class
    public const MAPPER = 2; // Classname that implements MapperInterface
    public const SOURCE = 3;
    public const REPOSITORY = 4; // Classname that implements RepositoryInterface
    public const DATABASE = 5; // Database name
    public const TABLE = 6; // Table name in the database
    public const PRIMARY_KEY = 7;
    public const FIND_BY_KEYS = 8;
    public const COLUMNS = 9;
    public const RELATIONS = 10;
    public const CHILDREN = 11; // List of entity sub-roles and their types
    public const SCOPE = 12;
    public const TYPECAST = 13; // Typecast rules
    public const SCHEMA = 14;
    public const PARENT = 15; // Parent role in the inheritance hierarchy
    public const PARENT_KEY = 16;
    public const DISCRIMINATOR = 17; // Discriminator column name for single table inheritance
    public const LISTENERS = 18;
    public const TYPECAST_HANDLER = 19; // Typecast handler definition that implements TypecastInterface
    public const GENERATED_FIELDS = 20; // List of generated fields [field => generating type]

    /**
     * Return all roles defined within the schema.
     */
    public function getRoles(): array;

    /**
     * Get name of relations associated with given entity role.
     */
    public function getRelations(string $role): array;

    /**
     * Check if the given role has a definition within the schema.
     */
    public function defines(string $role): bool;

    /**
     * Define schema value.
     *
     * Example: $schema->define(User::class, SchemaInterface::DATABASE);
     *
     * @param int $property See ORM constants.
     *
     * @throws SchemaException
     */
    public function define(string $role, int $property): mixed;

    /**
     * Define options associated with specific entity relation.
     *
     * @throws SchemaException
     */
    public function defineRelation(string $role, string $relation): array;

    /**
     * Resolve the role name using entity class name.
     */
    public function resolveAlias(string $role): ?string;

    /**
     * Get children roles for JTI resolving
     *
     * @param string $parent Parent role
     *
     * @return array<string, array> Tree of children
     */
    public function getInheritedRoles(string $parent): array;
}
