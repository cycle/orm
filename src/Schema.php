<?php

declare(strict_types=1);

namespace Cycle\ORM;

use Cycle\ORM\Exception\SchemaException;

/**
 * Static schema with automatic class name => role aliasing.
 */
final class Schema implements SchemaInterface
{
    private array $aliases;

    /**
     * @var string[]
     *
     * @psalm-var class-string[]
     */
    private array $classes = [];

    /** @var array<string, array> */
    private array $subclasses = [];

    private array $schema;

    public function __construct(array $schema)
    {
        // split into two?
        [$this->schema, $this->aliases] = $this->normalize($schema);
    }

    public function getRoles(): array
    {
        return \array_keys($this->schema);
    }

    public function getRelations(string $role): array
    {
        return \array_keys($this->define($role, self::RELATIONS));
    }

    /**
     * Return all defined roles with the schema.
     */
    public function toArray(): array
    {
        return $this->schema;
    }

    /**
     * @return array [role => [relation name => relation schema]]
     */
    public function getOuterRelations(string $role): array
    {
        // return null;
        $result = [];
        foreach ($this->schema as $roleName => $entitySchema) {
            foreach ($entitySchema[SchemaInterface::RELATIONS] ?? [] as $relName => $item) {
                if ($item[Relation::TARGET] === $role) {
                    $result[$roleName][$relName] = $item;
                } elseif ($item[Relation::TYPE] === Relation::MANY_TO_MANY) {
                    $through = $this->resolveAlias($item[Relation::SCHEMA][Relation::THROUGH_ENTITY]);
                    if ($through !== $role) {
                        continue;
                    }
                    $handshake = $item[Relation::SCHEMA][Relation::INVERSION] ?? null;
                    $target = $item[Relation::TARGET];
                    $result[$roleName][$relName] = [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => $role,
                        Relation::SCHEMA => [
                            Relation::CASCADE => $item[Relation::SCHEMA][Relation::CASCADE] ?? null,
                            Relation::INNER_KEY => $item[Relation::SCHEMA][Relation::INNER_KEY],
                            Relation::OUTER_KEY => $item[Relation::SCHEMA][Relation::THROUGH_INNER_KEY],
                        ],
                    ];
                    $result[$target][$handshake ?? ($roleName . '.' . $relName . ':' . $target)] = [
                        Relation::TYPE => Relation::HAS_MANY,
                        Relation::TARGET => $role,
                        Relation::SCHEMA => [
                            Relation::CASCADE => $item[Relation::SCHEMA][Relation::CASCADE] ?? null,
                            Relation::INNER_KEY => $item[Relation::SCHEMA][Relation::OUTER_KEY],
                            Relation::OUTER_KEY => $item[Relation::SCHEMA][Relation::THROUGH_OUTER_KEY],
                        ],
                    ];
                }
            }
        }
        return $result;
    }

    /**
     * @return array [relation name => relation schema]
     */
    public function getInnerRelations(string $role): array
    {
        return $this->schema[$role][SchemaInterface::RELATIONS] ?? [];
    }

    public function defines(string $role): bool
    {
        return isset($this->schema[$role]) || isset($this->aliases[$role]);
    }

    public function define(string $role, int $property): mixed
    {
        if ($property === SchemaInterface::ENTITY) {
            return $this->defineEntityClass($role);
        }
        $role = $this->resolveAlias($role) ?? $role;

        if (!isset($this->schema[$role])) {
            throw new SchemaException("Undefined schema `{$role}`, not found.");
        }

        return $this->schema[$role][$property] ?? null;
    }

    public function defineRelation(string $role, string $relation): array
    {
        $relations = $this->define($role, self::RELATIONS);

        if (!isset($relations[$relation])) {
            throw new SchemaException("Undefined relation `{$role}`.`{$relation}`.");
        }

        return $relations[$relation];
    }

    public function resolveAlias(string $role): ?string
    {
        // walk through all children until parent entity found
        $found = $this->aliases[$role] ?? null;
        while ($found !== null && $found !== $role) {
            $role = $found;
            $found = $this->aliases[$found] ?? null;
        }

        return $role;
    }

    public function getInheritedRoles(string $parent): array
    {
        return $this->subclasses[$parent] ?? [];
    }

    public static function __set_state(array $an_array): self
    {
        $schema = new self([]);
        $schema->schema = $an_array['schema'];
        $schema->aliases = $an_array['aliases'];

        return $schema;
    }

    /**
     * Automatically replace class names with their aliases.
     *
     * @return array Pair of [schema, aliases]
     */
    private function normalize(array $schema): array
    {
        $result = $aliases = [];

        foreach ($schema as $key => $item) {
            $role = $key;
            if (!isset($item[self::ENTITY])) {
                // legacy type of declaration (class => schema)
                $item[self::ENTITY] = $key;
            }

            if (\class_exists($key)) {
                $role = $item[self::ROLE] ?? $key;
                if ($role !== $key) {
                    $aliases[$key] = $role;
                }
            }

            if ($item[self::ENTITY] !== $role && \class_exists($item[self::ENTITY])) {
                $aliases[$item[self::ENTITY]] = $role;
                $this->classes[$role] = $item[self::ENTITY];
            }

            unset($item[self::ROLE]);
            $result[$role] = $item;
        }

        // Normalize PARENT option
        foreach ($result as $role => &$item) {
            if (isset($item[self::PARENT])) {
                if (\class_exists($item[self::PARENT])) {
                    $parent = $item[self::PARENT];
                    while (isset($aliases[$parent])) {
                        $parent = $aliases[$parent];
                    }
                    $item[self::PARENT] = $parent;
                }
                $this->subclasses[$role] ??= [];
                $this->subclasses[$item[self::PARENT]][$role] = &$this->subclasses[$role];
            }
        }
        unset($item);

        // Extract aliases from CHILDREN options
        foreach ($result as $role => $item) {
            if (isset($item[self::CHILDREN])) {
                foreach ($item[self::CHILDREN] as $child) {
                    if (isset($aliases[$child]) && \class_exists($child)) {
                        $aliases[$aliases[$child]] = $role;
                    }
                    $aliases[$child] = $role;
                }
            }
        }

        // Normalize relation associations
        foreach ($result as &$item) {
            if (isset($item[self::RELATIONS])) {
                $item[self::RELATIONS] = \iterator_to_array($this->normalizeRelations(
                    $item[self::RELATIONS],
                    $aliases,
                ));
            }
        }
        unset($item);

        $result = $this->linkRelations($result, $aliases);

        return [$result, $aliases];
    }

    private function normalizeRelations(array $relations, array $aliases): \Generator
    {
        foreach ($relations as $name => &$rel) {
            $target = $rel[Relation::TARGET];
            while (isset($aliases[$target])) {
                $target = $aliases[$target];
            }

            $rel[Relation::TARGET] = $target;

            $nullable = $rel[Relation::SCHEMA][Relation::NULLABLE] ?? null;
            // Transform not nullable RefersTo to BelongsTo
            if ($rel[Relation::TYPE] === Relation::REFERS_TO && $nullable === false) {
                $rel[Relation::TYPE] = Relation::BELONGS_TO;
            }

            // Normalize THROUGH_ENTITY value
            if ($rel[Relation::TYPE] === Relation::MANY_TO_MANY) {
                $through = $rel[Relation::SCHEMA][Relation::THROUGH_ENTITY];
                while (isset($aliases[$through])) {
                    $through = $aliases[$through];
                }
                $rel[Relation::SCHEMA][Relation::THROUGH_ENTITY] = $through;
            }

            yield $name => $rel;
        }
    }

    private function linkRelations(array $schemaArray, array $aliases): array
    {
        $result = $schemaArray;
        foreach ($result as $role => $item) {
            foreach ($item[self::RELATIONS] ?? [] as $container => $relation) {
                $target = $relation[Relation::TARGET];
                if (!\array_key_exists($target, $result)) {
                    continue;
                }
                $targetSchema = $result[$target];
                $targetRelations = $targetSchema[self::RELATIONS] ?? [];
                $inversion = $relation[Relation::SCHEMA][Relation::INVERSION] ?? null;
                if ($inversion !== null) {
                    if (!\array_key_exists($inversion, $targetRelations)) {
                        throw new SchemaException(
                            \sprintf(
                                'Relation `%s` as inversion of `%s.%s` not found in the `%s` role.',
                                $inversion,
                                $role,
                                $container,
                                $target,
                            ),
                        );
                    }
                    $targetHandshake = $targetRelations[$inversion][Relation::SCHEMA][Relation::INVERSION] ?? null;
                    if ($targetHandshake !== null && $container !== $targetHandshake) {
                        throw new SchemaException(
                            \sprintf(
                                'Relation `%s.%s` can\'t be inversion of `%s.%s` because they have different relation values.',
                                $role,
                                $container,
                                $target,
                                $inversion,
                            ),
                        );
                    }
                    $result[$target][self::RELATIONS][$inversion][Relation::SCHEMA][Relation::INVERSION] = $container;
                    continue;
                }
                // find inverted relation
                $inversion = $this->findInvertedRelation($role, $container, $relation, $targetRelations);
                if ($inversion === null) {
                    continue;
                }
                $result[$role][self::RELATIONS][$container][Relation::SCHEMA][Relation::INVERSION] = $inversion;
                $result[$target][self::RELATIONS][$inversion][Relation::SCHEMA][Relation::INVERSION] = $container;
            }
        }

        return $result;
    }

    private function findInvertedRelation(
        string $role,
        string $container,
        array $relation,
        array $targetRelations,
    ): ?string {
        $nullable = $relation[Relation::SCHEMA][Relation::NULLABLE] ?? null;
        /** @var callable $compareCallback */
        $compareCallback = match ($relation[Relation::TYPE]) {
            Relation::MANY_TO_MANY => [$this, 'compareManyToMany'],
            Relation::BELONGS_TO => [$this, 'checkBelongsToInversion'],
            // Relation::HAS_ONE, Relation::HAS_MANY => $nullable === true ? Relation::REFERS_TO : Relation::BELONGS_TO,
            default => null,
        };
        if ($compareCallback === null) {
            return null;
        }
        foreach ($targetRelations as $targetContainer => $targetRelation) {
            $targetSchema = $targetRelation[Relation::SCHEMA];
            if ($role !== $targetRelation[Relation::TARGET]) {
                continue;
            }
            if (isset($targetSchema[Relation::INVERSION])) {
                if ($targetSchema[Relation::INVERSION] === $container) {
                    // This target relation will be checked in the linkRelations() method
                    return null;
                }
                continue;
            }
            if ($compareCallback($relation, $targetRelation)) {
                return $targetContainer;
            }
        }
        return null;
    }

    private function compareManyToMany(array $relation, array $targetRelation): bool
    {
        $schema = $relation[Relation::SCHEMA];
        $targetSchema = $targetRelation[Relation::SCHEMA];
        // MTM connects with MTM only
        if ($targetRelation[Relation::TYPE] !== Relation::MANY_TO_MANY) {
            return false;
        }
        // Pivot entity should be same
        if ($schema[Relation::THROUGH_ENTITY] !== $targetSchema[Relation::THROUGH_ENTITY]) {
            return false;
        }
        // Same keys
        if ((array) $schema[Relation::INNER_KEY] !== (array) $targetSchema[Relation::OUTER_KEY]
            || (array) $schema[Relation::OUTER_KEY] !== (array) $targetSchema[Relation::INNER_KEY]) {
            return false;
        }
        // Optional fields
        return !(($schema[Relation::WHERE] ?? []) !== ($targetSchema[Relation::WHERE] ?? [])
            || ($schema[Relation::THROUGH_WHERE] ?? []) !== ($targetSchema[Relation::THROUGH_WHERE] ?? []));
    }

    private function checkBelongsToInversion(array $relation, array $targetRelation): bool
    {
        $schema = $relation[Relation::SCHEMA];
        $targetSchema = $targetRelation[Relation::SCHEMA];
        // MTM connects with MTM only
        if (!\in_array($targetRelation[Relation::TYPE], [Relation::HAS_MANY, Relation::HAS_ONE], true)) {
            return false;
        }
        // Same keys
        if ((array) $schema[Relation::INNER_KEY] !== (array) $targetSchema[Relation::OUTER_KEY]
            || (array) $schema[Relation::OUTER_KEY] !== (array) $targetSchema[Relation::INNER_KEY]) {
            return false;
        }
        // Optional fields
        return empty($schema[Relation::WHERE]) && empty($targetSchema[Relation::WHERE]);
    }

    /**
     * @psalm-return null|class-string
     */
    private function defineEntityClass(string $role): ?string
    {
        if (\array_key_exists($role, $this->classes)) {
            return $this->classes[$role];
        }
        $rr = $this->resolveAlias($role) ?? $role;
        return $this->classes[$rr]
            ?? $this->schema[$rr][self::ENTITY]
            ?? throw new SchemaException("Undefined schema `{$role}`, not found.");
    }
}
