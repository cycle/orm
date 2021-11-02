<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\MapperTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class MapperTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
