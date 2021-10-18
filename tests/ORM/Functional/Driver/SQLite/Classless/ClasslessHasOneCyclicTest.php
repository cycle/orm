<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Classless;

/**
 * @group driver
 * @group driver-sqlite
 */
class ClasslessHasOneCyclicTest extends \Cycle\ORM\Tests\Functional\Classless\ClasslessHasOneCyclicTest
{
    public const DRIVER = 'sqlite';
}
