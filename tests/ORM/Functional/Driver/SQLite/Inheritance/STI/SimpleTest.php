<?php

declare(strict_types=1);

namespace Cycle\ORM\Tests\Functional\Driver\SQLite\Inheritance\STI;

// phpcs:ignore
use Cycle\ORM\Tests\Functional\Driver\Common\Inheritance\STI\SimpleTest as CommonClass;

/**
 * @group driver
 * @group driver-sqlite
 */
class SimpleTest extends CommonClass
{
    public const DRIVER = 'sqlite';
}
