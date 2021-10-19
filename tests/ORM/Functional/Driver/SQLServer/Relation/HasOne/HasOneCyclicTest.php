<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCyclicTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasOneCyclicTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
