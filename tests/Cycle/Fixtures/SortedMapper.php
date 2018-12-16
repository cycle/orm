<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\Cycle\Tests\Fixtures;


use Spiral\Cycle\Mapper\Mapper;
use Spiral\Cycle\Selector\QueryConstrain;
use Spiral\Cycle\Selector\ConstrainInterface;

// Sort all records by default
class SortedMapper extends Mapper
{
    public function getConstrain(string $name = self::DEFAULT_CONSTRAIN): ?ConstrainInterface
    {
        return new QueryConstrain([], ['@.id' => 'ASC']);
    }
}