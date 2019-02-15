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

class Index
{
    public const SCHEMA = [
        'columns' => [Parser::ARRAY => 'string'],
        'unique'  => 'bool'
    ];
}