<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\CompositePKTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CompositePKTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
