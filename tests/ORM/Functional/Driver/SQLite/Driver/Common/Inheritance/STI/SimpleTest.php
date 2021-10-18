<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Driver\Common\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest as CommonTest;

/**
 * @group driver
 * @group driver-sqlite
 */
class SimpleTest extends CommonTest
{
    public const DRIVER = 'sqlite';
}
