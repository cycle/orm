<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\RenamedPKTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RenamedPKTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
