<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\UserDefined;

use Spiral\ORM\MapperInterface;
use Spiral\ORM\RelationMap;

class TestMapper implements MapperInterface
{
    public function make(array $data, RelationMap $relmap = null)
    {
        return new TestEntity($data);
    }
}