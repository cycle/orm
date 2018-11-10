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
use Zend\Hydrator\Reflection;

class TestMapper implements MapperInterface
{
    private $hydrator;

    public function __construct()
    {
        $this->hydrator = new Reflection();
    }

    public function make(array $data, RelationMap $relmap = null)
    {
        return $this->hydrator->hydrate($data, new TestEntity());
    }
}