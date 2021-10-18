<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\JTI\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\JTI\Relation\HierarchyInRelationTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class HierarchyInRelationTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
