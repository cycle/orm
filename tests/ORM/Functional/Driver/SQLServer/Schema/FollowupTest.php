<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Schema;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Schema\FollowupTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class FollowupTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
