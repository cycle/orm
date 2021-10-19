<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\PaginateTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class PaginateTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
