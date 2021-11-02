<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclingManyToManyWithTimestampsTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class CyclingManyToManyWithTimestampsTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
