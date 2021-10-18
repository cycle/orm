<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Relation\DeepCyclicTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeepCyclicTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
