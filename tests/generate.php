<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

use Spiral\Tokenizer;

error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', true);

//Composer
require dirname(__DIR__) . '/vendor/autoload.php';

$tokenizer = new Tokenizer\Tokenizer(new Tokenizer\Config\TokenizerConfig([
    'directories' => [__DIR__],
    'exclude'     => []
]));


$databases = [
    'sqlite'    => [
        'namespace' => 'Spiral\ORM\Tests\Driver\SQLite',
        'directory' => __DIR__ . '/ORM/Driver/SQLite/'
    ],
    'mysql'     => [
        'namespace' => 'Spiral\ORM\Tests\Driver\MySQL',
        'directory' => __DIR__ . '/ORM/Driver/MySQL/'
    ],
    'postgres'  => [
        'namespace' => 'Spiral\ORM\Tests\Driver\Postgres',
        'directory' => __DIR__ . '/ORM/Driver/Postgres/'
    ],
//    'sqlserver' => [
//        'namespace' => 'Spiral\ORM\Tests\Driver\SQLServer',
//        'directory' => __DIR__ . '/ORM/Driver/SQLServer/'
//    ]
];

echo "Generating test classes for all database types...\n";

$classes = $tokenizer->classLocator()->getClasses(\Spiral\ORM\Tests\BaseTest::class);

foreach ($classes as $class) {
    if (!$class->isAbstract() || $class->getName() == \Spiral\ORM\Tests\BaseTest::class) {
        continue;
    }

    echo "Found {$class->getName()}\n";
    foreach ($databases as $driver => $details) {
        $filename = sprintf("%s/%s.php", $details['directory'], $class->getShortName());

        file_put_contents(
            $filename,
            sprintf(
                '<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
 
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

//dump($classes);