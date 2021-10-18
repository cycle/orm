<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\RefersTo;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\RefersTo\RefersToProxyMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class RefersToProxyMapperTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
