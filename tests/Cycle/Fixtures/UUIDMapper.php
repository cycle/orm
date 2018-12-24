<?php
/**
 * Spiral Framework.
 *
 * @license   MIT
 * @author    Anton Titov (Wolfy-J)
 */
declare(strict_types=1);

namespace Spiral\Cycle\Tests\Fixtures;

use Ramsey\Uuid\Uuid;
use Spiral\Cycle\Exception\MapperException;
use Spiral\Cycle\Mapper\Mapper;

class UUIDMapper extends Mapper
{
    /**
     * Generate entity primary key value.
     */
    public function generatePrimaryKey()
    {
        try {
            return Uuid::uuid4()->toString();
        } catch (\Exception $e) {
            throw new MapperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}