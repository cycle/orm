<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\STI\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\Mapper\ManyToManyPromiseMapperTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class ManyToManyPromiseMapperTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
