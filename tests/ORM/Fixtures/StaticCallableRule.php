<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\Database\DatabaseInterface;

class StaticCallableRule
{
    public static function invoke(string $value, DatabaseInterface $database, array $arguments): array
    {
        return [
            'value' => $value,
            'database' => $database,
            'arguments' => $arguments,
        ];
    }
}
