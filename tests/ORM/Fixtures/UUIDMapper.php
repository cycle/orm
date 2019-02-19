<?php
declare(strict_types=1);
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */

namespace Cycle\ORM\Tests\Fixtures;

use Ramsey\Uuid\Uuid;
use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Mapper\Mapper;

class UUIDMapper extends Mapper
{
    /**
     * Generate entity primary key value.
     */
    public function nextPrimaryKey()
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            throw new MapperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}