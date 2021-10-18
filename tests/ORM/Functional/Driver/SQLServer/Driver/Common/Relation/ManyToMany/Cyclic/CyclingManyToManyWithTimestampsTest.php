<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclingManyToManyWithTimestampsTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclingManyToManyWithTimestampsTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
