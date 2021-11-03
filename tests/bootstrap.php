<?php

declare(strict_types=1);

use Cycle\Database;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$drivers = [
    'sqlite' => new Database\Config\SQLiteDriverConfig(
        queryCache: true,
    ),
    'mysql' => new Database\Config\MySQLDriverConfig(
        connection: new Database\Config\MySQL\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 13306,
            user: 'root',
            password: 'root',
        ),
        queryCache: true
    ),
    'postgres' => new Database\Config\PostgresDriverConfig(
        connection: new Database\Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'postgres',
        ),
        schema: 'public',
        queryCache: true,
    ),
    'sqlserver' => new Database\Config\SQLServerDriverConfig(
        connection: new Database\Config\SQLServer\TcpConnectionConfig(
            database: 'tempdb',
            host: '127.0.0.1',
            port: 11433,
            user: 'SA',
            password: 'SSpaSS__1'
        ),
        queryCache: true
    ),
];

$db = getenv('DB') ?: null;
\Cycle\ORM\Tests\Functional\Driver\Common\BaseTest::$config = [
    'debug' => false,
    'strict' => true,
    'benchmark' => true,
] + (
    $db === null
        ? $drivers
        : array_intersect_key($drivers, array_flip((array)$db))
);
