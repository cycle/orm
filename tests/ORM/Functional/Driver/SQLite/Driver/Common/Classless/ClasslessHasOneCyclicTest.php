<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Classless;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Classless\ClasslessHasOneCyclicTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class ClasslessHasOneCyclicTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
