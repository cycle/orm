<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLServer\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\HierarchyInRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlserver
 */
class HierarchyInRelationTest extends CommonClass
{
    public const DRIVER = 'sqlserver';
}
