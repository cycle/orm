<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasMany;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasManyCollectionsTest extends \Cycle\ORM\Tests\Functional\Relation\HasMany\HasManyCollectionsTest
{
    public const DRIVER = 'sqlserver';
}
