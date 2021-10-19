<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SingleTableTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SingleTableTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
