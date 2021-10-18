<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\HasMany;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasMany\HasManyProxyMapperTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyProxyMapperTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
