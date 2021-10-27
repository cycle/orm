<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SimpleCasesTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SimpleCasesTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
