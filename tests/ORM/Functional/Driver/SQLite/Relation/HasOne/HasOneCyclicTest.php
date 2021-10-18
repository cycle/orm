<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Relation\HasOne\HasOneCyclicTest
{
    public const DRIVER = 'sqlite';
}
