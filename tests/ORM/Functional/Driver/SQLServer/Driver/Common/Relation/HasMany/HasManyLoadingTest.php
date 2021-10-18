<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyLoadingTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyLoadingTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
