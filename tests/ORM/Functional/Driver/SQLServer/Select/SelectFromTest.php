<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\SelectFromTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SelectFromTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
