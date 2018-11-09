<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\SchemaException;

/**
 * Provides access to compiled ORM schema.
 */
final class Schema implements SchemaInterface
{
    public const ALIAS       = 0;
    public const MAPPER      = 1;
    public const SOURCE      = 2;
    public const DATABASE    = 3;
    public const TABLE       = 4;
    public const PRIMARY_KEY = 5;
    public const SCHEMA      = 6;
    public const RELATIONS   = 7;


    /** @var array */
    private $schema = [];

    /**
     * @param array $schema
     */
    public function __construct(array $schema)
    {
        $this->schema = $schema;
    }

    /**
     * @inheritdoc
     */
    public function define(string $class, int $property)
    {
        //Check value
        if (!isset($this->schema[$class])) {
            throw new SchemaException("Undefined schema '{$class}', schema not found.");
        }

        if (!array_key_exists($property, $this->schema[$class])) {
            throw new SchemaException("Undefined schema property '{$class}'.'{$property}', property not found.");
        }

        return $this->schema[$class][$property];
    }

    /**
     * @inheritdoc
     */
    public function export(string $class, array $properties): array
    {
        $result = [];

        foreach ($properties as $property) {
            $result[$property] = $this->define($class, $property);
        }

        return $result;
    }
}