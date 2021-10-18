<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Relation\HasOne;

/**
 * @group driver
 * @group driver-sqlite
 */
class HasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Driver\Common\Relation\HasOne\HasOneCyclicTest
{
    public const DRIVER = 'sqlite';
}
