<?php

declare(strict_types=1);

use Cycle\Database\Config;

error_reporting(E_ALL);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$drivers = [
    'sqlite' => new Config\SQLiteDriverConfig(
        queryCache: true,
        options: [
            'logInterpolatedQueries' => true,
        ],
    ),
    'mysql' => new Config\MySQLDriverConfig(
        connection: new Config\MySQL\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 13306,
            charset: 'utf8mb4',
            user: 'root',
            password: 'YourStrong!Passw0rd',
        ),
        queryCache: true,
        options: [
            'logInterpolatedQueries' => true,
        ],
    ),
    'postgres' => new Config\PostgresDriverConfig(
        connection: new Config\Postgres\TcpConnectionConfig(
            database: 'spiral',
            host: '127.0.0.1',
            port: 15432,
            user: 'postgres',
            password: 'YourStrong!Passw0rd',
        ),
        schema: 'public',
        queryCache: true,
        options: [
            'logInterpolatedQueries' => true,
        ],
    ),
    'sqlserver' => new Config\SQLServerDriverConfig(
        connection: new Config\SQLServer\DsnConnectionConfig(
            'sqlsrv:Server=127.0.0.1,11433;Database=tempdb;TrustServerCertificate=true',
            user: 'SA',
            password: 'YourStrong!Passw0rd',
        ),
        queryCache: true,
        options: [
            'logInterpolatedQueries' => true,
        ],
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
        : array_intersect_key($drivers, array_flip((array) $db))
);
