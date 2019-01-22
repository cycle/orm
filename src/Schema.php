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

/**
 * Static schema with automatic class name => role aliasing.
 */
final class Schema implements SchemaInterface
{
    /** @var array */
    private $schema = [];

    /**
     * @param array $schema
     */
    public function __construct(array $schema)
    {
        // split into two?
        $this->schema = $this->normalize($schema);
    }

    /**
     * @inheritdoc
     */
    public function resolveRole(string $entity): ?string
    {
        while (isset($this->schema[$entity][self::ALIAS])) {
            $entity = $this->schema[$entity][self::ALIAS];
        }

        return $entity;
    }

    /**
     * @inheritdoc
     */
    public function defines(string $role): bool
    {
        return array_key_exists($role, $this->schema);
    }

    /**
     * @inheritdoc
     */
    public function define(string $role, int $property)
    {
        $role = $this->resolveRole($role);
        if (!isset($this->schema[$role])) {
            throw new SchemaException("Undefined schema `{$role}`, not found.");
        }

        if (!array_key_exists($property, $this->schema[$role])) {
            return null;
        }

        return $this->schema[$role][$property];
    }

    /**
     * @inheritdoc
     */
    public function defineRelation(string $class, string $relation): array
    {
        $relations = $this->define($class, self::RELATIONS);

        if (!isset($relations[$relation])) {
            throw new SchemaException("Undefined relation `{$class}`.`{$relation}`.");
        }

        return $relations[$relation];
    }

    /**
     * Automatically replace class names with their aliases.
     *
     * @param array $schema
     * @return array
     */
    protected function normalize(array $schema): array
    {
        $aliases = iterator_to_array($this->collectClasses($schema));

        $result = [];
        foreach ($schema as $k => $item) {
            if (isset($item[self::RELATIONS])) {
                $item[self::RELATIONS] = iterator_to_array($this->normalizeRelations(
                    $item[self::RELATIONS],
                    $aliases
                ));
            }

            // assume that key is class name
            $item[self::ENTITY] = $item[self::ENTITY] ?? $k;

            $role = $k;

            // legacy format where role is defined as key
            if (isset($item[self::ROLE])) {
                $role = $item[self::ROLE];
                unset($item[self::ROLE]);
            }

            $result[$role] = $item;
        }

        // return aliases to their location
        foreach ($aliases as $name => $role) {
            $result[$name] = [self::ROLE => $role];
        }

        return $result;
    }

    /**
     * @param array $schema
     * @return \Generator
     */
    private function collectClasses(array $schema): \Generator
    {
        foreach ($schema as $k => $item) {
            if (!isset($item[self::ENTITY])) {
                $item[self::ENTITY] = $k;
            }

            if (class_exists($item[self::ENTITY]) && isset($item[self::ROLE])) {
                yield$item[self::ENTITY] => $item[self::ROLE];
            }
        }
    }

    /**
     * @param array $relations
     * @param array $aliases
     * @return \Generator
     */
    private function normalizeRelations(array $relations, array $aliases): \Generator
    {
        foreach ($relations as $name => &$rl) {
            $target = $rl[Relation::TARGET];

            if (isset($aliases[$target])) {
                $rl[Relation::TARGET] = $aliases[$target];
            }

            yield $name => $rl;
        }
    }
}