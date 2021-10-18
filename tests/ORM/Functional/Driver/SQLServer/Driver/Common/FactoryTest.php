<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\FactoryTest as CommonTest; 
 
/**
 * @group driver
 * @group driver-sqlserver
 */
class FactoryTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
