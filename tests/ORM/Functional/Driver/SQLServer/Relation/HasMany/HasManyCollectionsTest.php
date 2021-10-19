<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyCollectionsTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyCollectionsTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
