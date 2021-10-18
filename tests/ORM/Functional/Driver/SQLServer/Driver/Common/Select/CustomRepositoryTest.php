<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Select;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Select\CustomRepositoryTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CustomRepositoryTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
