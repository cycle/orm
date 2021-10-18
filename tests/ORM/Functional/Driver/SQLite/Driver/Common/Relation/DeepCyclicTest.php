<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation;

/**
 * @group driver
 * @group driver-sqlite
 */
class DeepCyclicTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\DeepCyclicTest
{
    public const DRIVER = 'sqlite';
}
