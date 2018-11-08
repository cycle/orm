<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Treap;

use Spiral\Database\Config\DatabaseConfig;
use Spiral\Database\DatabaseInterface;
use Spiral\Database\DatabaseManager;
use Spiral\Database\Driver\SQLite\SQLiteDriver;
use Spiral\Treap\Exception\SchemaException;

class ORM implements ORMInterface
{
    private $dbal;

    /** @var array */
    private $schema = [];

    public function __construct()
    {
        $cfg = new DatabaseConfig([
            'default'   => 'default',
            'databases' => [
                'default' => ['driver' => 'runtime']
            ],
            'drivers'   => [
                'runtime' => [
                    'driver'     => SQLiteDriver::class,
                    'connection' => 'sqlite::memory:',
                    'username'   => 'sqlite',
                ],
            ]
        ]);

        $this->dbal = new DatabaseManager($cfg);
        $this->schema = [];

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


    public function database(string $class): DatabaseInterface
    {
        return $this->dbal->database(
        //    $this->getSchema()->define($class, Schema::DATABASE)
        );
    }

    public function selector(string $class): Selector
    {
        return new Selector($this, $class);
    }
}