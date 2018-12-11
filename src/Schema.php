<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

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
        $this->schema = $schema;


        $add = [];
        // fixing stuff
        foreach ($this->schema as $k => &$item) {
            $item[self::ENTITY] = $k;

            if (isset($item[self::RELATIONS])) {
                foreach ($item[self::RELATIONS] as &$rel) {
                    if (isset($this->schema[$rel[Relation::TARGET]][self::ALIAS])) {
                        $rel[Relation::TARGET] = $this->schema[$rel[Relation::TARGET]][self::ALIAS];
                    }

                    unset($rel);
                }
            }

            if (isset($item[self::ALIAS])) {
                $add[$item[self::ALIAS]] = $item;
            }

            unset($item);
        }

        $this->schema = $this->schema + $add;
    }

    /**
     * @inheritdoc
     */
    public function define(string $class, int $property)
    {
        if (!isset($this->schema[$class])) {
            throw new SchemaException("Undefined schema `{$class}`, not found.");
        }

        if (!array_key_exists($property, $this->schema[$class])) {
            return null;
        }

        return $this->schema[$class][$property];
    }

    public function getClass(string $alias): ?string
    {
        foreach ($this->schema as $class => $schema) {
            if ($schema[self::ALIAS] == $alias) {
                return $class;
            }
        }

        return null;
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