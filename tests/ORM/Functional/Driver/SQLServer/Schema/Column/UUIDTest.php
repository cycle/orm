<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Schema\Column;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\Column\UUIDTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class UUIDTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
