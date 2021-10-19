<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\MapperTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class MapperTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
