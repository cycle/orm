<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\FactoryTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class FactoryTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
