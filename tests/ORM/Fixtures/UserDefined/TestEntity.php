<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Spiral\ORM\Tests\Fixtures\UserDefined;

use Spiral\Models\DataEntity;

class TestEntity extends DataEntity
{
    public function __debugInfo()
    {
        return $this->getFields();
    }
}