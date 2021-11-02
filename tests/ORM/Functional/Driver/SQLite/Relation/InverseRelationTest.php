<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\InverseRelationTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class InverseRelationTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
