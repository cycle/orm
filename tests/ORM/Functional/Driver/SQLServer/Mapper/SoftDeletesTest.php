<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Mapper;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Mapper\SoftDeletesTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SoftDeletesTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
