<?php

/**
 * Cycle DataMapper ORM
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

declare(strict_types=1);

use Spiral\Tokenizer;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', '1');

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$tokenizer = new Tokenizer\Tokenizer(new Tokenizer\Config\TokenizerConfig([
    'directories' => [__DIR__],
    'exclude' => [],
]));

$databases = [
    'sqlite' => [
        'namespace' => 'Cycle\ORM\Tests\Driver\SQLite',
        'directory' => __DIR__ . '/ORM/Driver/SQLite/',
    ],
    'mysql' => [
        'namespace' => 'Cycle\ORM\Tests\Driver\MySQL',
        'directory' => __DIR__ . '/ORM/Driver/MySQL/',
    ],
    'postgres' => [
        'namespace' => 'Cycle\ORM\Tests\Driver\Postgres',
        'directory' => __DIR__ . '/ORM/Driver/Postgres/',
    ],
    'sqlserver' => [
        'namespace' => 'Cycle\ORM\Tests\Driver\SQLServer',
        'directory' => __DIR__ . '/ORM/Driver/SQLServer/',
    ],
];

echo "Generating test classes for all database types...\n";

$classes = $tokenizer->classLocator()->getClasses(\Cycle\ORM\Tests\BaseTest::class);

foreach ($classes as $class) {
    if (!$class->isAbstract() || $class->getName() == \Cycle\ORM\Tests\BaseTest::class) {
        continue;
    }

    echo "Found {$class->getName()}\n";
    foreach ($databases as $driver => $details) {
        $filename = sprintf('%s/%s.php', $details['directory'], $class->getShortName());

        file_put_contents(
            $filename,
            sprintf(
                '<?php

declare(strict_types=1);

namespace %s;

class %s extends \%s
{
    const DRIVER = "%s";
}',
                $details['namespace'],
                $class->getShortName(),
                $class->getName(),
                $driver
            )
        );
    }
}

// helper to validate the selection results
// file_put_contents('out.php', '<?php ' . var_export($selector->fetchData(), true));
