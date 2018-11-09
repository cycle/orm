<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Treap\Exception\SchemaException;

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
     * Define multiple schema properties.
     *
     * @param string $class
     * @param array  $properties
     * @return array
     *
     * @throws SchemaException
     */
    public function export(string $class, array $properties): array;
}