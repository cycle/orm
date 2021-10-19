<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\ManyToMany\Cyclic;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\Cyclic\CyclingManyToManyWithTimestampsTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class CyclingManyToManyWithTimestampsTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
