<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Driver\Common\Inheritance\STI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyPromiseMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
