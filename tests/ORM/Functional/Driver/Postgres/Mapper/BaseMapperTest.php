<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\BaseMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-postgres
 */
class BaseMapperTest extends CommonTest
{
    public const DRIVER = 'postgres';
}
