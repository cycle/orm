<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Integration\Case428;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Integration\Case428\TestCase as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class TestCase extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
