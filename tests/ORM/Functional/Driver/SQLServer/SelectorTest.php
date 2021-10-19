<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\SelectorTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SelectorTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
