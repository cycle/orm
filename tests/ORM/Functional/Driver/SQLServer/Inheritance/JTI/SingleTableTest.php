<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI;

/**
 * @group driver
 * @group driver-sqlserver
 */
class SingleTableTest extends \Cycle\ORM\Tests\Functional\Inheritance\JTI\SingleTableTest
{
    public const DRIVER = 'sqlserver';
}
