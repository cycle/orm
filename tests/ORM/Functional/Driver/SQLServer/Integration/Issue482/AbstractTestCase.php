<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Integration\Issue482;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Issue482\AbstractTestCase as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class AbstractTestCase extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
