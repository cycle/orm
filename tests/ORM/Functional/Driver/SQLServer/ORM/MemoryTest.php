<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\ORM;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORM\MemoryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class MemoryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
