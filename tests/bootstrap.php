<?php

declare(strict_types=1);

use Cycle\Database;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$drivers = [
    'sqlite' => [
        'driver' => Database\Driver\SQLite\SQLiteDriver::class,
        'conn' => 'sqlite::memory:',
        'user' => 'sqlite',
        'pass' => '',
        'queryCache' => 100,
    ],
    'mysql' => [
        'driver' => Database\Driver\MySQL\MySQLDriver::class,
        'conn' => 'mysql:host=127.0.0.1:13306;dbname=spiral',
        'user' => 'root',
        'pass' => 'root',
        'queryCache' => 100,
    ],
    'postgres' => [
        'driver' => Database\Driver\Postgres\PostgresDriver::class,
        'conn' => 'pgsql:host=127.0.0.1;port=15432;dbname=spiral',
        'user' => 'postgres',
        'pass' => 'postgres',
        'queryCache' => 100,
    ],
    'sqlserver' => [
        'driver' => Database\Driver\SQLServer\SQLServerDriver::class,
        'conn' => 'sqlsrv:Server=127.0.0.1,11433;Database=tempdb',
        'user' => 'SA',
        'pass' => 'SSpaSS__1',
        'queryCache' => 100,
    ],
];

$db = getenv('DB') ?: null;
\Cycle\ORM\Tests\BaseTest::$config = [
    'debug' => false,
    'strict' => true,
    'benchmark' => true,
] + (
        $db === null
        ? $drivers
        : array_intersect_key($drivers, array_flip((array)$db))
    );
