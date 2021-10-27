<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\InverseRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class InverseRelationTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
