<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\Postgres\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\HierarchyInRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-postgres
 */
class HierarchyInRelationTest extends CommonClass
{
    public const DRIVER = 'postgres';
}
