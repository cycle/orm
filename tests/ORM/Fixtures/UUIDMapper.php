<?php

// phpcs:ignoreFile
declare(strict_types=1);

namespace Cycle\ORM\Tests\Fixtures;

use Cycle\ORM\Exception\MapperException;
use Cycle\ORM\Mapper\Mapper;
use Ramsey\Uuid\Uuid;

class UUIDMapper extends Mapper
{
    /**
     * Generate entity primary key value.
     */
    public function nextPrimaryKey(): array
    {
        try {
            return [$this->primaryKeys[0] => Uuid::uuid4()->toString()];
        } catch (\Exception $e) {
            throw new MapperException($e->getMessage(), $e->getCode(), $e);
        }
    }
}
