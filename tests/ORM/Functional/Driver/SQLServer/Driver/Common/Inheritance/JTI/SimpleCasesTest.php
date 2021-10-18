<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Inheritance\JTI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\SimpleCasesTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SimpleCasesTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
