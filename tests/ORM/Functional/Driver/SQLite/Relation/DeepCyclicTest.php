<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeepCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\DeepCyclicTest
{
    public const DRIVER = 'sqlite';
}
