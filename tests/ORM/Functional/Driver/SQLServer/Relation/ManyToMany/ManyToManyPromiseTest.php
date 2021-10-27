<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\ManyToMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\ManyToMany\ManyToManyPromiseTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class ManyToManyPromiseTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
