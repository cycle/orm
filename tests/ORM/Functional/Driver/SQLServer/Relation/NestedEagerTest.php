<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\NestedEagerTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class NestedEagerTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
