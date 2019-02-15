<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Schema\Annotated\Node;

class Column
{
    public const SCHEMA = [
        'type'     => 'string',
        'nullable' => 'bool',
        'name'     => 'string',
        'default'  => 'mixed'
    ];

    public $type;
    public $nullable;
    public $name;
    public $default;
}