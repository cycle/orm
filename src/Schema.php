<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle;

use Spiral\Cycle\Exception\SchemaException;

/**
 * Provides access to compiled ORM schema.
 *
 * @todo: improve?
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
     * @param array $schema
     * @return array
     */
    protected function collectAliases(array $schema): array
    {
        $aliases = [];
        foreach ($schema as $k => $item) {
            if (!isset($item[self::ENTITY])) {
                $item[self::ENTITY] = $k;
            }

            if (class_exists($item[self::ENTITY]) && isset($item[self::ROLE])) {
                $aliases[$item[self::ENTITY]] = $item[self::ROLE];
            }
        }

        $result = [];
        foreach ($schema as $k => $item) {
            if (isset($item[self::RELATIONS])) {
                // convert all class pointers to role pointers
                foreach ($item[self::RELATIONS] as &$rel) {
                    $target = $rel[Relation::TARGET];

                    if (isset($aliases[$target])) {
                        $rel[Relation::TARGET] = $aliases[$target];
                    }

                    unset($rel);
                }
            }

            if (isset($item[self::ROLE])) {
                $role = $item[self::ROLE];
                unset($item[self::ROLE]);
            } else {
                $role = $k;
            }

            if (!isset($item[self::ENTITY]) && class_exists($k)) {
                $item[self::ENTITY] = $k;
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
     * @return array
     */
    protected function normalize(array $schema): array
    {
        $aliases = [];
        foreach ($schema as $k => $item) {
            if (!isset($item[self::ENTITY])) {
                $item[self::ENTITY] = $k;
            }

            if (class_exists($item[self::ENTITY]) && isset($item[self::ROLE])) {
                $aliases[$item[self::ENTITY]] = $item[self::ROLE];
            }
        }

        $result = [];
        foreach ($schema as $k => $item) {
            if (isset($item[self::RELATIONS])) {
                // convert all class pointers to role pointers
                foreach ($item[self::RELATIONS] as &$rel) {
                    $target = $rel[Relation::TARGET];

                    if (isset($aliases[$target])) {
                        $rel[Relation::TARGET] = $aliases[$target];
                    }

                    unset($rel);
                }
            }

            if (isset($item[self::ROLE])) {
                $role = $item[self::ROLE];
                unset($item[self::ROLE]);
            } else {
                $role = $k;
            }

            if (!isset($item[self::ENTITY]) && class_exists($k)) {
                $item[self::ENTITY] = $k;
            }


            $result[$role] = $item;
        }

        // return aliases to their location
        foreach ($aliases as $name => $role) {
            $result[$name] = [self::ROLE => $role];
        }

        return $result;
    }

    public function defines(string $role): bool
    {
        return array_key_exists($role, $this->schema);
    }

    /**
     * @inheritdoc
     */
    public function resolveRole(string $name): ?string
    {
        return $this->schema[$name][self::ALIAS] ?? $name;
    }

    /**
     * @inheritdoc
     */
    public function define(string $role, int $property)
    {
        if (!isset($this->schema[$role])) {
            throw new SchemaException("Undefined schema `{$role}`, not found.");
        }

        if (isset($this->schema[$role][self::ALIAS])) {
            // class => role
            $role = $this->schema[$role][self::ALIAS];
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
}