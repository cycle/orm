<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated\Node;

use Spiral\Cycle\Schema\Annotated\Parser;

class Table
{
    public const SCHEMA = [
        'name'     => 'string',
        'database' => 'string',
        'table'    => [Parser::ALIAS => 'name'],
        'indexes'  => [Parser::ARRAY => Index::class]
    ];

    public $name;
    public $database;
    public $indexes = [];
}