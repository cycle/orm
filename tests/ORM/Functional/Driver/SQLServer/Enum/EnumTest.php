<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Enum;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Enum\EnumTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class EnumTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
