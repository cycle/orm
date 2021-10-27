<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasOneRelationTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
