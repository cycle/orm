<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyLoadingTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyLoadingTest
{
    public const DRIVER = 'sqlserver';
}
