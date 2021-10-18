<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class NestedEagerTest extends \Cycle\ORM\Tests\Functional\Relation\NestedEagerTest
{
    public const DRIVER = 'sqlite';
}
