<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\DeepCyclicTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class DeepCyclicTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
