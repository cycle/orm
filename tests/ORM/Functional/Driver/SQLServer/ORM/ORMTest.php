<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\ORM;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\ORM\ORMTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ORMTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
