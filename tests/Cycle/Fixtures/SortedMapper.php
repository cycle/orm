<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Select\QueryConstrain;
use Spiral\Cycle\Select\ConstrainInterface;

// Sort all records by default
class SortedMapper extends Mapper
{
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?ConstrainInterface
    {
        return new QueryConstrain([], ['@.id' => 'ASC']);
    }
}