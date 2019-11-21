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

/**
 * Static schema with automatic class name => role aliasing.
 */
final class Schema implements SchemaInterface
{
    /** @var array */
    private $aliases;

    /** @var array */
    private $schema = [];

    /**
     * @param array $schema
     */
    public function __construct(array $schema)
    {
        // split into two?
        [$this->schema, $this->aliases] = $this->normalize($schema);
    }

    /**
     * @param array $an_array
     * @return Schema
     */
    public static function __set_state($an_array): Schema
    {
        $schema = new self([]);
        $schema->schema = $an_array['schema'];
        $schema->aliases = $an_array['aliases'];

        return $schema;
    }

    /**
     * @inheritdoc
     */
    public function getRoles(): array
    {
        return array_keys($this->schema);
    }

    /**
     * @inheritdoc
     */
    public function getRelations(string $role): array
    {
        return array_keys($this->define($role, self::RELATIONS));
    }

    /**
     * @inheritdoc
     */
    public function defines(string $role): bool
    {
        return array_key_exists($role, $this->schema) || array_key_exists($role, $this->aliases);
    }

    /**
     * @inheritdoc
     */
    public function define(string $role, int $property)
    {
        $role = $this->resolveAlias($role) ?? $role;

        if (!isset($this->schema[$role])) {
            throw new SchemaException("Undefined schema `{$role}`, not found");
        }

        if (!array_key_exists($property, $this->schema[$role])) {
            return null;
        }

        return $this->schema[$role][$property];
    }

    /**
     * @inheritdoc
     */
    public function defineRelation(string $role, string $relation): array
    {
        $relations = $this->define($role, self::RELATIONS);

        if (!isset($relations[$relation])) {
            throw new SchemaException("Undefined relation `{$role}`.`{$relation}`");
        }

        return $relations[$relation];
    }

    /**
     * @inheritdoc
     */
    public function resolveAlias(string $entity): ?string
    {
        // walk throught all children until parent entity found
        while (isset($this->aliases[$entity])) {
            $entity = $this->aliases[$entity];
        }

        return $entity;
    }


    /**
     * Automatically replace class names with their aliases.
     *
     * @param array $schema
     * @return array Pair of [schema, aliases]
     */
    protected function normalize(array $schema): array
    {
        $result = $aliases = [];

        foreach ($schema as $key => $item) {
            $role = $key;
            if (!isset($item[self::ENTITY])) {
                // legacy type of declaration (class => schema)
                $item[self::ENTITY] = $key;
            }

            if (class_exists($key)) {
                $role = $item[self::ROLE] ?? $key;
                if ($role !== $key) {
                    $aliases[$key] = $role;
                }
            }

            if ($item[self::ENTITY] !== $role && class_exists($item[self::ENTITY])) {
                $aliases[$item[self::ENTITY]] = $role;
            }

            unset($item[self::ROLE]);
            $result[$role] = $item;
        }

        // normalizing relation associations
        foreach ($result as &$item) {
            if (isset($item[self::RELATIONS])) {
                $item[self::RELATIONS] = iterator_to_array($this->normalizeRelations(
                    $item[self::RELATIONS],
                    $aliases
                ));
            }

            unset($item);
        }

        return [$result, $aliases];
    }

    /**
     * @param array $relations
     * @param array $aliases
     * @return \Generator
     */
    private function normalizeRelations(array $relations, array $aliases): \Generator
    {
        foreach ($relations as $name => &$rel) {
            $target = $rel[Relation::TARGET];
            while (isset($aliases[$target])) {
                $target = $aliases[$target];
            }

            $rel[Relation::TARGET] = $target;

            yield $name => $rel;
        }
    }
}
