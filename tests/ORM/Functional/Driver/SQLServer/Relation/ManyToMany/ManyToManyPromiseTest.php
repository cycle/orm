<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyPromiseTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyPromiseTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
