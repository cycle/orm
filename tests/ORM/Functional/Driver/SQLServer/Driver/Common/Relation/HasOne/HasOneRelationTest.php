<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Driver\Common\Relation\HasOne;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HasOneRelationTest extends CommonTest
{
    public const DRIVER = 'sqlserver';
}
